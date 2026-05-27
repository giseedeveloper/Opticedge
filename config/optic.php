<?php

return [

    /*
    |--------------------------------------------------------------------------
    | External db:seed query password
    |--------------------------------------------------------------------------
    |
    | Used by GET /db/migrate?pass=…, /db/seed?pass=…, /db/setup/run?pass=…
    | and the setup page at /db/setup. Default pass is 1234.
    | Override with OPTIC_DB_SEED_PASS in .env. Seed optional: &class=YourSeeder.
    |
    */
    'db_seed_pass' => env('OPTIC_DB_SEED_PASS', '1234'),

];
