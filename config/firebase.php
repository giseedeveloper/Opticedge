<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging (FCM HTTP v1)
    |--------------------------------------------------------------------------
    |
    | Set FIREBASE_ENABLED=true and place your service account JSON at the
    | path below. Push notifications are skipped when disabled or misconfigured.
    |
    */

    'enabled' => env('FIREBASE_ENABLED', false),

    'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase-credentials.json')),

    'project_id' => env('FIREBASE_PROJECT_ID', ''),

];
