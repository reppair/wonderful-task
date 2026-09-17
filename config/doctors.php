<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Doctors Data Source
    |--------------------------------------------------------------------------
    |
    | The `doctors:ingest` command pulls the doctors dataset from this URL. When
    | no URL is configured it falls back to the bundled JSON file so that local
    | development and tests work without network access.
    |
    */

    'source' => [
        'url' => env('DOCTORS_SOURCE_URL'),
        'path' => env('DOCTORS_SOURCE_PATH', database_path('helthcate_data.json')),
        'timeout' => (int) env('DOCTORS_SOURCE_TIMEOUT', 30),
    ],

];
