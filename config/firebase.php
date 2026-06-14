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

    /*
     * Absolute path to the Firebase service account JSON.
     * Relative paths in FIREBASE_CREDENTIALS are resolved from the Laravel root.
     */
    'credentials' => (function (): string {
        $configured = env('FIREBASE_CREDENTIALS');
        if (! is_string($configured) || $configured === '') {
            return storage_path('app/firebase-credentials.json');
        }

        if (str_starts_with($configured, DIRECTORY_SEPARATOR)
            || (strlen($configured) > 1 && ctype_alpha($configured[0]) && $configured[1] === ':')) {
            return $configured;
        }

        return base_path($configured);
    })(),

    'project_id' => env('FIREBASE_PROJECT_ID', ''),

];
