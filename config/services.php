<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'eu-central-1'),
    ],

    'google' => [
        'client_id' => env('SSO_GOOGLE_KEY'),
        'client_secret' => env('SSO_GOOGLE_SECRET'),
        'redirect' => env('APP_URL') . env('SSO_GOOGLE_CALLBACK_URL'),
    ],

    'facebook' => [
        'client_id' => env('SSO_FACEBOOK_KEY'),
        'client_secret' => env('SSO_FACEBOOK_SECRET'),
        'redirect' => env('APP_URL') . env('SSO_FACEBOOK_CALLBACK_URL'),
    ],

	'apple' => [
        'client_id' => env('SSO_APPLE_KEY'),
        'client_secret' => env('SSO_APPLE_SECRET'),
        'redirect' => env('APP_URL') . env('SSO_APPLE_CALLBACK_URL'),
    ],

	'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
