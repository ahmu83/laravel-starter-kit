<?php

use App\Services\WordPressUserSync;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
  $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('test:wp-sync {email?}', function ($email = null) {
  $sync = app(WordPressUserSync::class);

  if ($email) {
    $this->info("Syncing user: {$email}");
    $user = $sync->syncUserByEmail($email);
  } else {
    $this->info("Syncing first WordPress user...");
    $wpUser = DB::connection('wordpress')->table('users')->first();
    $user   = $sync->syncUserByEmail($wpUser->user_email);
  }

  if ($user) {
    $this->info("✓ Success! Synced user #{$user->id}");
    $this->table(
      ['ID', 'Name', 'Email', 'WP ID', 'Roles'],
      [[$user->id, $user->name, $user->email, $user->wp_user_id, json_encode($user->wp_roles)]]
    );
  } else {
    $this->error("✗ Failed to sync user");
  }
})->purpose('Test WordPress user sync');

