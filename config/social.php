<?php
// config/social.php

return [

    /*
    |--------------------------------------------------------------------------
    | Enabled Providers
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'google',
        'github',
        'facebook',
        'twitter',
        'linkedin',
        'microsoft',
        'apple',
    ],

    /*
    |--------------------------------------------------------------------------
    | Active Providers (with credentials configured)
    |--------------------------------------------------------------------------
    */
    'active' => [
        'google', // Only Google is active
    ],

    /*
    |--------------------------------------------------------------------------
    | Email/Password Authentication
    |--------------------------------------------------------------------------
    |
    | Enable or disable traditional email/password login and registration.
    | When disabled, users can only authenticate via social providers.
    |
    */
    'email_login_enabled' => false,    // Set to true to enable email/password login
    'email_register_enabled' => false, // Set to true to enable email/password registration

    /*
    |--------------------------------------------------------------------------
    | Provider Scopes
    |--------------------------------------------------------------------------
    */
    'scopes' => [
        'google' => [
            // Default: userinfo.email, userinfo.profile, openid
        ],

        'github' => [
            // Default: user:email
        ],

        'facebook' => [
            // Default: email, public_profile
        ],

        'twitter' => [
            // Default: users.read, tweet.read
        ],

        'linkedin' => [
            // Default: r_liteprofile, r_emailaddress
            // 'r_basicprofile',
        ],

        'microsoft' => [
            // Default: openid, profile, email
            // 'User.Read',
        ],

        'apple' => [
            // Default: name, email
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Account Linking Strategy
    |--------------------------------------------------------------------------
    */
    'account_linking' => 'link',

    /*
    |--------------------------------------------------------------------------
    | Error Messages
    |--------------------------------------------------------------------------
    */
    'errors' => [
        'auth_failed' => 'Authentication with :provider failed. Please try again.',
        'account_exists' => 'An account with this email already exists. Please login with your password first, then link your :provider account from your profile.',
        'access_denied' => 'You denied access. Please try again and grant the necessary permissions.',
        'invalid_provider' => 'Invalid social login provider.',
        'coming_soon' => ':provider login coming soon!',
    ],

];
