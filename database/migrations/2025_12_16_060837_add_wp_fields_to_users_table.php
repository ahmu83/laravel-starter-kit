<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('users', function (Blueprint $table) {

      $table->unsignedBigInteger('wp_user_id')->nullable()->index()->after('id');

      $table->json('wp_roles')->nullable()->after('wp_user_id');

      $table->string('wp_user_login', 60)->nullable()->after('wp_roles');

    });
  }

  public function down(): void {
    Schema::table('users', function (Blueprint $table) {
      $table->dropIndex(['wp_user_id']);
      $table->dropColumn([
        'wp_user_id',
        'wp_roles',
        'wp_user_login',
      ]);
    });
  }
};
