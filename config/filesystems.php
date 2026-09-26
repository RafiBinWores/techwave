<?php

$s3 = [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    'throw' => true,
    'report' => false,
];

$uploadsDisk = env('UPLOADS_DISK', 'local');

if (! in_array($uploadsDisk, ['local', 's3'], true)) {
    throw new InvalidArgumentException('UPLOADS_DISK must be local or s3.');
}

/*
|--------------------------------------------------------------------------
| Legacy disks (pre dual-storage)
|--------------------------------------------------------------------------
|
| These mirror the original env-driven disks. The dual driver embeds them as
| a read/delete fallback until every file has been backfilled to the clouds
| with `php artisan storage:sync-existing`.
|
*/

$legacyPublic = $uploadsDisk === 's3' ? [...$s3, 'root' => 'uploads'] : [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
    'visibility' => 'public',
    'throw' => true,
    'report' => false,
];

$legacyDocuments = $uploadsDisk === 's3' ? [...$s3, 'root' => 'documents'] : [
    'driver' => 'local',
    'root' => storage_path('app/private'),
    'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/private-documents',
    'serve' => true,
    'throw' => true,
    'report' => false,
];

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        /* Logical disks fan every write out to AWS S3 and Cloudflare R2 (when
           configured via the admin Storage page) and serve from the selected
           render source, falling back to the legacy disk for old files. */
        'public' => [
            'driver' => 'dual',
            'disk_name' => 'public',
            'root' => 'uploads',
            'legacy' => $legacyPublic,
            'throw' => true,
            'report' => false,
        ],

        'documents' => [
            'driver' => 'dual',
            'disk_name' => 'documents',
            'root' => 'documents',
            'legacy' => $legacyDocuments,
            'throw' => true,
            'report' => false,
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
            'report' => false,
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
