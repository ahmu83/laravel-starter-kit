<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('node_meta', function (Blueprint $table) {
            $table->id()->comment('Primary ID');

            $table->string('meta_type', 255)
                ->comment('The type of the parent entity (order, user, product, etc.)');

            $table->unsignedBigInteger('meta_id')
                ->comment('The ID of the parent entity (e.g., order id, user id, etc.)');

            $table->string('meta_key', 255)
                ->comment('Meta key, e.g., "admin_note", "tracking_note", etc.');

            $table->string('meta_key_type', 255)
                ->default('default')
                ->comment('Type of the node_meta_key key. "default" will be the default value.');

            $table->longText('meta_value')
                ->nullable()
                ->comment('Meta value, can store large text data like notes, JSON, etc.');

            $table->unsignedBigInteger('created_by')
                ->nullable()
                ->comment('User ID who created this meta record. Will default to null');

            $table->timestamps();

            // Indexes
            $table->index(['meta_type', 'meta_id', 'meta_key', 'updated_at'], 'node_meta_type_id_key_updated_idx');
            $table->index(['meta_type', 'meta_id', 'meta_key_type'], 'node_meta_type_id_keytype_idx');
            $table->index(['meta_type', 'meta_id', 'created_by'], 'node_meta_type_id_creator_idx');
            $table->index(['meta_key', 'meta_key_type'], 'node_meta_key_keytype_idx');
            $table->index('created_by', 'node_meta_created_by_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('node_meta');
    }
};
