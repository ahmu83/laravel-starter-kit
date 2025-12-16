<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    // Only seed users in local/dev
    if (! app()->environment('local')) {
      return;
    }

    // Ensure roles exist
    $adminRole = Role::firstOrCreate(['name' => 'admin']);
    $userRole  = Role::firstOrCreate(['name' => 'subscriber']);

    // Test admin user
    $admin = User::firstOrCreate(
      ['email' => 'admin@example.com'],
      [
        'name' => 'Admin User',
        'password' => Hash::make('password'),
        'wp_user_id' => null,
        'wp_roles' => null,
      ]
    );

    $admin->syncRoles([$adminRole]);

    // Regular test user
    $user = User::firstOrCreate(
      ['email' => 'test@example.com'],
      [
        'name' => 'Test User',
        'password' => Hash::make('password'),
        'wp_user_id' => null,
        'wp_roles' => null,
      ]
    );

    $user->syncRoles([$userRole]);
  }
}
