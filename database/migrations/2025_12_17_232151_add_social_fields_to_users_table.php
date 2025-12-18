<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('social_provider')->nullable()->after('password');
            $table->string('social_provider_id')->nullable()->after('social_provider'); // Fixed
            $table->string('social_avatar_url')->nullable()->after('social_provider_id'); // Fixed

            // Index for faster lookups
            $table->index(['social_provider', 'social_provider_id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['social_provider', 'social_provider_id']);
            $table->dropColumn(['social_provider', 'social_provider_id', 'social_avatar_url']);
        });
    }
};
