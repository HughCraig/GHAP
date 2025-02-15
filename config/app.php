<?php

return [

    //env vars
    'maxpaging' => env('MAX_PAGING', false),
    'defaultpaging' => env('DEFAULT_PAGING', false),

    /*
    |--------------------------------------------------------------------------
    | Help video URL
    |--------------------------------------------------------------------------
    |
    | The URL of the help video in search form. If it's from YouTube, remember to add rel=0&enablejsapi=1 query string
    | at the end of the embed URL to hide more videos and enable some more functions. Omit this variable will completely
    | disable the help video feature.
    |
    */
    'help_video_url' => env('HELP_VIDEO_URL', null),

    # Whether to show help video on first landing
    # 1: Show
    # 0: Do not show
    'show_help_video_first_landing' => env('SHOW_HELP_VIDEO_FIRST_LANDING', '0'),

    # The max upload image size allowed in bytes. Default is 4MB.
    'max_upload_image_size' => env('MAX_UPLOAD_IMAGE_SIZE', 4194304),

    # The max upload file size for text allowed in bytes. Default is 10MB.
    'text_max_upload_file_size' => env('TEXT_MAX_UPLOAD_FILE_SIZE', 2500000),

    // Define allowed text file types
    'allowed_text_file_types' => explode(',', env('ALLOWED_TEXT_FILE_TYPES', 'txt,docx')),

    //TEXT map geoparsing api route
    'geoparsing_api_url' => env('GEOPARSING_API_URL', 'https://geoparsing.textmap.tlcmap.org/api/geoparse'),

    //TEXT map geocoding api route
    'geocoding_api_url' => env('GEOCODING_API_URL', 'https://geocoding.textmap.tlcmap.org/api/geocode'),

    //Test map api kep
    'geoparsing_api_key' => env('GEOPARSING_API_KEY' , 'GSAP-APNR-MxroY7QYIANG8YLDidq9MLEqknsI1oui'),

    /*
    |--------------------------------------------------------------------------
    | Views root URL
    |--------------------------------------------------------------------------
    |
    | The root URL of TLCMap views.
    |
    */
    'views_root_url' => env('VIEWS_ROOT_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Temporal earth visualisation URL
    |--------------------------------------------------------------------------
    |
    | The URL of TLCMap temporal earth visualisation.
    |
    */
    'views_temporal_earth_url' => env('VIEWS_TEMPORAL_EARTH_URL', null),

    /*
    |--------------------------------------------------------------------------
    | The URL OF THE TLCMAP DOCUMENTATION
    |--------------------------------------------------------------------------
    |
    */
    'tlcmap_doc_url' => env('TLCMAP_DOC_URL', null),


    /*
    |--------------------------------------------------------------------------
    | The Default number of places shown on home page
    |--------------------------------------------------------------------------
    | 100 , 200 ,500 or 2000 or "ALL"
    */
    "home_page_places_shown" => env('HOME_PAGE_PLACES_SHOWN', 200),
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'TLCMap'),

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

    /*
    |--------------------------------------------------------------------------
    | Application version
    |--------------------------------------------------------------------------
    |
    | This tracks the current version of the application. It should be changed
    | to the according version number before each release. Also should be
    | consistent with the version tags in GitHub.
    |
    */
    'version' => '8.0.0',

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

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost:8000'),

    'asset_url' => env('ASSET_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'Australia/Sydney',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Package Service Providers...
         */

        /*
         * Application Service Providers...
         */
        TLCMap\Providers\AppServiceProvider::class,
        TLCMap\Providers\AuthServiceProvider::class,
        // TLCMap\Providers\BroadcastServiceProvider::class,
        TLCMap\Providers\EventServiceProvider::class,
        TLCMap\Providers\RouteServiceProvider::class,
        'Chumper\Zipper\ZipperServiceProvider'

    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [

        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Str' => Illuminate\Support\Str::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,
        'Zipper' => 'Chumper\Zipper\Zipper',

    ],

];
