<?php

namespace App\Services;

use App\Models\NodeMeta;
use Illuminate\Support\Collection;

class NodeMetaService
{
  /**
   * Create a new meta row (no deduping).
   *
   * @param  string  $metaType
   * @param  int     $metaId
   * @param  string  $metaKey
   * @param  mixed   $metaValue
   * @param  string  $metaKeyType
   * @param  int|null  $createdBy
   * @return NodeMeta
   */
  public function add(
    string $metaType,
    int $metaId,
    string $metaKey,
    $metaValue,
    string $metaKeyType = 'default',
    ?int $createdBy = null
  ): NodeMeta {
    $data = [
      'meta_type'     => $metaType,
      'meta_id'       => $metaId,
      'meta_key'      => $metaKey,
      'meta_key_type' => $metaKeyType,
      'meta_value'    => $this->maybeEncodeValue($metaValue),
      'created_by'    => $createdBy,
      'created_at'    => now(),
      'updated_at'    => now(),
    ];

    return NodeMeta::create($data);
  }

  /**
   * Update the latest meta row for a given meta_type + meta_id + key.
   * If no record exists, automatically creates a new one.
   *
   * @param  string  $metaType
   * @param  int     $metaId
   * @param  string  $metaKey
   * @param  mixed   $metaValue
   * @param  string  $metaKeyType
   * @param  int|null  $createdBy  Only used if creating new record
   * @return bool    true if updated or added successfully
   */
  public function update(
    string $metaType,
    int $metaId,
    string $metaKey,
    $metaValue,
    string $metaKeyType = 'default',
    ?int $createdBy = null
  ): bool {
    $latest = NodeMeta::where('meta_type', $metaType)
      ->where('meta_id', $metaId)
      ->where('meta_key', $metaKey)
      ->orderByDesc('id')
      ->first();

    if (!$latest) {
      // No existing record — create a new one
      return (bool) $this->add($metaType, $metaId, $metaKey, $metaValue, $metaKeyType, $createdBy);
    }

    $latest->meta_value = $this->maybeEncodeValue($metaValue);
    $latest->meta_key_type = $metaKeyType;
    $latest->updated_at = now();

    return (bool) $latest->save();
  }

  /**
   * Update all meta rows for a given meta_type + meta_id + key.
   *
   * @param  string  $metaType
   * @param  int     $metaId
   * @param  string  $metaKey
   * @param  mixed   $metaValue
   * @param  string  $metaKeyType
   * @return int     number of rows updated
   */
  public function updateAll(
    string $metaType,
    int $metaId,
    string $metaKey,
    $metaValue,
    string $metaKeyType = 'default'
  ): int {
    return NodeMeta::where('meta_type', $metaType)
      ->where('meta_id', $metaId)
      ->where('meta_key', $metaKey)
      ->update([
        'meta_value'    => $this->maybeEncodeValue($metaValue),
        'meta_key_type' => $metaKeyType,
        'updated_at'    => now(),
      ]);
  }

  /**
   * Get a single meta value. By default returns the latest value for the key.
   *
   * @param  string      $metaType
   * @param  int         $metaId
   * @param  string      $metaKey
   * @param  mixed|null  $default
   * @param  bool        $latestOnly
   * @return mixed
   */
  public function get(
    string $metaType,
    int $metaId,
    string $metaKey,
    $default = null,
    bool $latestOnly = true
  ) {
    $query = NodeMeta::where('meta_type', $metaType)
      ->where('meta_id', $metaId)
      ->where('meta_key', $metaKey);

    $row = $latestOnly
      ? $query->orderByDesc('id')->first()
      : $query->orderBy('id')->first();

    if (!$row) {
      return $default;
    }

    return $this->maybeDecodeValue($row->meta_value);
  }

  /**
   * Get a single meta row with all details. By default returns the latest for the key.
   *
   * @param  string  $metaType
   * @param  int     $metaId
   * @param  string  $metaKey
   * @param  bool    $latestOnly
   * @return array|null
   */
  public function getRow(
    string $metaType,
    int $metaId,
    string $metaKey,
    bool $latestOnly = true
  ): ?array {
    $query = NodeMeta::where('meta_type', $metaType)
      ->where('meta_id', $metaId)
      ->where('meta_key', $metaKey);

    $row = $latestOnly
      ? $query->orderByDesc('id')->first()
      : $query->orderBy('id')->first();

    if (!$row) {
      return null;
    }

    return [
      'id'            => $row->id,
      'meta_type'     => $row->meta_type,
      'meta_id'       => $row->meta_id,
      'meta_key'      => $row->meta_key,
      'meta_key_type' => $row->meta_key_type,
      'meta_value'    => $this->maybeDecodeValue($row->meta_value),
      'created_by'    => $row->created_by,
      'created_at'    => $row->created_at,
      'updated_at'    => $row->updated_at,
    ];
  }

  /**
   * Get all meta rows for a node (optionally filtered by key).
   * Returns a collection of arrays with decoded values.
   *
   * @param  string       $metaType
   * @param  int          $metaId
   * @param  string|null  $metaKey
   * @return \Illuminate\Support\Collection<array>
   */
  public function getAll(string $metaType, int $metaId, ?string $metaKey = null): Collection
  {
    $query = NodeMeta::where('meta_type', $metaType)
      ->where('meta_id', $metaId)
      ->orderBy('id');

    if ($metaKey !== null) {
      $query->where('meta_key', $metaKey);
    }

    return $query->get()->map(fn(NodeMeta $row) => $this->mapRowToArray($row));
  }

  /**
   * Get all meta rows for a node filtered by key type.
   *
   * @param  string  $metaType
   * @param  int     $metaId
   * @param  string  $metaKeyType
   * @return \Illuminate\Support\Collection<array>
   */
  public function getAllByKeyType(string $metaType, int $metaId, string $metaKeyType): Collection
  {
    return NodeMeta::where('meta_type', $metaType)
      ->where('meta_id', $metaId)
      ->where('meta_key_type', $metaKeyType)
      ->orderBy('id')
      ->get()
      ->map(fn(NodeMeta $row) => $this->mapRowToArray($row));
  }

  /**
   * Get all meta rows created by a specific user.
   *
   * @param  string  $metaType
   * @param  int     $metaId
   * @param  int     $userId
   * @return \Illuminate\Support\Collection<array>
   */
  public function getAllByCreator(string $metaType, int $metaId, int $userId): Collection
  {
    return NodeMeta::where('meta_type', $metaType)
      ->where('meta_id', $metaId)
      ->where('created_by', $userId)
      ->orderBy('id')
      ->get()
      ->map(fn(NodeMeta $row) => $this->mapRowToArray($row));
  }

  /**
   * Delete only the latest meta row for a given meta_type + meta_id + key.
   *
   * @param  string  $metaType
   * @param  int     $metaId
   * @param  string  $metaKey
   * @return int     number of rows deleted (0 or 1)
   */
  public function delete(string $metaType, int $metaId, string $metaKey): int
  {
    $latest = NodeMeta::where('meta_type', $metaType)
      ->where('meta_id', $metaId)
      ->where('meta_key', $metaKey)
      ->orderByDesc('id')
      ->first();

    if (!$latest) {
      return 0;
    }

    return (int) $latest->delete();
  }

  /**
   * Delete all meta rows for a given meta_type + meta_id + key.
   *
   * @param  string  $metaType
   * @param  int     $metaId
   * @param  string  $metaKey
   * @return int     number of rows deleted
   */
  public function deleteAll(string $metaType, int $metaId, string $metaKey): int
  {
    return NodeMeta::where('meta_type', $metaType)
      ->where('meta_id', $metaId)
      ->where('meta_key', $metaKey)
      ->delete();
  }

  /**
   * Delete ALL meta rows for a specific node (meta_type + meta_id).
   * This is intentionally a different method to avoid accidental mass deletes.
   *
   * @param  string  $metaType
   * @param  int     $metaId
   * @return int     Number of rows deleted
   */
  public function deleteAllForNode(string $metaType, int $metaId): int
  {
    return NodeMeta::where('meta_type', $metaType)
      ->where('meta_id', $metaId)
      ->delete();
  }

  /**
   * Get valid meta types from config.
   *
   * @return array
   */
  public function getValidTypes(): array
  {
    return NodeMeta::getValidTypes();
  }

  /**
   * Check if a meta type is valid.
   *
   * @param  string  $metaType
   * @return bool
   */
  public function isValidType(string $metaType): bool
  {
    return in_array($metaType, $this->getValidTypes(), true);
  }

  /**
   * Map a NodeMeta model to an array.
   *
   * @param  NodeMeta  $row
   * @return array
   */
  protected function mapRowToArray(NodeMeta $row): array
  {
    return [
      'id'            => $row->id,
      'meta_type'     => $row->meta_type,
      'meta_id'       => $row->meta_id,
      'meta_key'      => $row->meta_key,
      'meta_key_type' => $row->meta_key_type,
      'meta_value'    => $this->maybeDecodeValue($row->meta_value),
      'created_by'    => $row->created_by,
      'created_at'    => $row->created_at,
      'updated_at'    => $row->updated_at,
    ];
  }

  /**
   * Maybe encode a value before saving to the database.
   * Encodes arrays/objects to JSON; scalars are stored as-is.
   *
   * @param  mixed  $metaValue
   * @return string|int|float|bool|null
   */
  protected function maybeEncodeValue($metaValue)
  {
    return (is_array($metaValue) || is_object($metaValue))
      ? json_encode($metaValue)
      : $metaValue;
  }

  /**
   * Maybe decode a value retrieved from the database.
   * Handles JSON decoding; returns raw value otherwise.
   *
   * @param  mixed  $metaValue
   * @return mixed
   */
  protected function maybeDecodeValue($metaValue)
  {
    if (is_string($metaValue)) {
      $json = json_decode($metaValue, true);
      if (json_last_error() === JSON_ERROR_NONE) {
        return $json;
      }
    }

    // Return raw value (strings, numbers, booleans, null)
    return $metaValue;
  }

}

