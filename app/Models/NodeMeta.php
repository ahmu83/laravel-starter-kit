<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class NodeMeta extends Model
{
  protected $table = 'node_meta';

  protected $fillable = [
    'meta_type',
    'meta_id',
    'meta_key',
    'meta_key_type',
    'meta_value',
    'created_by',
  ];

  protected $casts = [
    'created_by' => 'integer',
  ];

  /**
   * Get valid node types from config
   */
  public static function getValidTypes(): array
  {
    return config('nodemeta.meta_types', []);
  }

  /**
   * Boot the model and add validation
   */
  protected static function boot()
  {
    parent::boot();

    static::creating(function ($model) {
      // Validate meta_type against config
      $validTypes = self::getValidTypes();
      if (!in_array($model->meta_type, $validTypes, true)) {
        throw ValidationException::withMessages([
          'meta_type' => "Invalid meta_type. Must be one of: " . implode(', ', $validTypes)
        ]);
      }
    });
  }

  /**
   * Get the parent node (polymorphic relationship)
   */
  public function node(): MorphTo
  {
    return $this->morphTo('node', 'meta_type', 'meta_id');
  }

  /**
   * Get the user who created this meta record
   * This can be either WordPress or Laravel user depending on your sync strategy
   */
  public function creator(): BelongsTo
  {
    return $this->belongsTo(User::class, 'created_by');
  }
}
