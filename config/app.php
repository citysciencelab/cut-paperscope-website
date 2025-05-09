<?php

return [

	/*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

	'name' => env('APP_NAME', 'Laravel'),


	/*
	|--------------------------------------------------------------------------
	| Project Features
	|--------------------------------------------------------------------------
	*/

	'features' => [
		'app_accounts' => 	env('FEATURE_APP_ACCOUNTS', false),
		'backend' => 		env('FEATURE_BACKEND', false),
		'backend_reset' => 	env('FEATURE_BACKEND_RESET', false),
		'shop' => 			env('FEATURE_SHOP', false),
		'multi_lang' => 	env('FEATURE_MULTI_LANG', false),
	],


	/*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

	'env' => env('APP_ENV', 'production'),

 	'ffmpeg' => env('FFMPEG_BINARY', '/usr/local/bin/ffmpeg'),
	'tinypng' => env('TINYPNG_KEY', ''),


	/*
	|--------------------------------------------------------------------------
	| Application Debug Mode
	|--------------------------------------------------------------------------
	|
	| When your application is in debug mode, detailed error messages with
	| stack traces will be shown on every error that occurs within your
	| application. If disabled, a simple generic error page is shown.
	|
	*/

	'debug' => (bool) env('APP_DEBUG', false),


	/*
	|--------------------------------------------------------------------------
	| Analytics
	|--------------------------------------------------------------------------
	*/

	'matomo_url' => env('MATOMO_URL', null),
	'matomo_token' => env('MATOMO_TOKEN', null),


	/*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

	'url' => env('APP_URL', 'http://localhost'),


	/*
	|--------------------------------------------------------------------------
	| Application Timezone
	|--------------------------------------------------------------------------
	|
	| Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

	'timezone' => env('APP_TIMEZONE', 'Europe/Berlin'),


	/*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'de'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'de'),
    'faker_locale' => env('APP_FAKER_LOCALE', 'de_DE'),
	'available_locales' => explode(',', str_replace(' ', '', env('APP_AVAILABLE_LOCALES', 'de,en'))),


	/*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

	'key' => env('APP_KEY'),

	'cipher' => 'AES-256-CBC',

	'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],


	/*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
