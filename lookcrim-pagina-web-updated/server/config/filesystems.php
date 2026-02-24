<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nota Cloud Run
    |--------------------------------------------------------------------------
    |
    | En Cloud Run el filesystem local es efímero. Para que las imágenes
    | (registers) sobrevivan reinicios, usá GCS y seteá:
    | - FILESYSTEM_PUBLIC_DRIVER=gcs
    | - GCS_BUCKET=...
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => (function () {
            $driver = env('FILESYSTEM_PUBLIC_DRIVER', 'local');

            if ($driver === 'gcs') {
                $bucket = env('GCS_BUCKET');
                $baseUrl = env('GCS_PUBLIC_URL');

                // Fallback público estándar (útil en staging)
                if (!$baseUrl && $bucket) {
                    $baseUrl = 'https://storage.googleapis.com/' . $bucket;
                }

                return [
                    'driver' => 'gcs',
                    'project_id' => env('GCS_PROJECT_ID'),
                    'bucket' => $bucket,
                    'path_prefix' => (string) (env('GCS_PATH_PREFIX') ?? ''),
                    'visibility' => 'public',
                    'url' => $baseUrl,
                    'throw' => false,
                ];
            }

            return [
                'driver' => 'local',
                'root' => storage_path('app/public'),
                'url' => env('APP_URL').'/storage',
                'visibility' => 'public',
                'throw' => false,
            ];
        })(),

        'gcs' => [
            'driver' => 'gcs',
            'project_id' => env('GCS_PROJECT_ID'),
            'bucket' => env('GCS_BUCKET'),
            'path_prefix' => (string) (env('GCS_PATH_PREFIX') ?? ''),
            'visibility' => 'public',
            'url' => env('GCS_PUBLIC_URL'),
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
