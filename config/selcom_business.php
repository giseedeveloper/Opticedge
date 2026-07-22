<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Selcom Business (Disbursement) API
    |--------------------------------------------------------------------------
    |
    | This is a DIFFERENT product from the Selcom Checkout API (config/selcom.php).
    | It sends money OUT to a recipient wallet/bank (agent commission payout) and
    | authenticates with RSA-SHA256 signatures using a downloaded private key.
    |
    | Docs: https://developer.selcom.business/
    */

    // Public identifier that pairs with the private key (from the Selcom Business
    // dashboard credential panel — this is NOT inside the .pem file).
    'api_key' => env('SELCOM_BUSINESS_API_KEY'),

    // Absolute or storage-relative path to the RSA private key (.pem) used to sign
    // requests. Keep this file OUT of version control (see .gitignore).
    'private_key_path' => env(
        'SELCOM_BUSINESS_PRIVATE_KEY_PATH',
        storage_path('app/selcom/selcom_business_private.pem')
    ),

    // The Selcom Business account/wallet number that funds disbursements. Required
    // for the balance check (POST /v1/balance) shown on the superadmin dashboard.
    'account_number' => env('SELCOM_BUSINESS_ACCOUNT_NUMBER'),

    // false = sandbox (no real money), true = production (real money moves).
    'live' => env('SELCOM_BUSINESS_IS_LIVE', false),

    'base_url' => [
        'sandbox' => env('SELCOM_BUSINESS_SANDBOX_URL', 'https://sandbox.selcom.business'),
        'live' => env('SELCOM_BUSINESS_LIVE_URL', 'https://api.selcom.business'),
    ],

    // Selcom transaction "purpose" code sent on each disbursement. Commission is a
    // business expense; adjust to a code your Selcom Business account supports.
    'purpose_code' => env('SELCOM_BUSINESS_PURPOSE_CODE', 'BUSINESS_EXPENSES'),

    /*
    | Map a normalized Tanzanian MSISDN (255XXXXXXXXX) prefix to the Selcom
    | recipient FI code for the mobile-money cash-in rail. Verify these against
    | your Selcom Business account's supported destination codes before going live.
    */
    'wallet_fi_codes' => [
        // Vodacom M-Pesa
        '25574' => 'VMCASHIN',
        '25575' => 'VMCASHIN',
        '25576' => 'VMCASHIN',
        // Airtel Money
        '25578' => 'AMCASHIN',
        '25568' => 'AMCASHIN',
        '25569' => 'AMCASHIN',
        // Tigo / Mixx by Yas
        '25571' => 'TPCASHIN',
        '25565' => 'TPCASHIN',
        '25567' => 'TPCASHIN',
        '25577' => 'TPCASHIN',
        // Halotel Halopesa
        '25562' => 'HPCASHIN',
        '25561' => 'HPCASHIN',
        // TTCL T-Pesa
        '25573' => 'TTCASHIN',
    ],
];
