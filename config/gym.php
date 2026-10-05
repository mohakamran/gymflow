<?php

use App\Notifications\Messaging\LogTextMessenger;
use App\Payments\ManualGateway;

return [

    /*
    |--------------------------------------------------------------------------
    | Supported currencies
    |--------------------------------------------------------------------------
    |
    | ISO 4217 code => [symbol, name]. Gyms pick one of these in their settings.
    |
    */

    'currencies' => [
        'USD' => ['$', 'US Dollar'],
        'EUR' => ['€', 'Euro'],
        'GBP' => ['£', 'British Pound'],
        'CAD' => ['$', 'Canadian Dollar'],
        'AUD' => ['$', 'Australian Dollar'],
        'AED' => ['د.إ', 'UAE Dirham'],
        'SAR' => ['﷼', 'Saudi Riyal'],
        'PKR' => ['Rs', 'Pakistani Rupee'],
        'INR' => ['₹', 'Indian Rupee'],
        'BDT' => ['৳', 'Bangladeshi Taka'],
        'NGN' => ['₦', 'Nigerian Naira'],
        'ZAR' => ['R', 'South African Rand'],
        'MYR' => ['RM', 'Malaysian Ringgit'],
        'SGD' => ['$', 'Singapore Dollar'],
    ],

    'locales' => [
        'en' => 'English',
    ],

    /*
    |--------------------------------------------------------------------------
    | Uploads
    |--------------------------------------------------------------------------
    |
    | SVG is intentionally not allowed for logos because it can carry scripts.
    |
    */

    'uploads' => [
        'image_mimes' => ['png', 'jpg', 'jpeg', 'webp'],
        'logo_max_kb' => 2048,
        'favicon_max_kb' => 512,
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment gateways
    |--------------------------------------------------------------------------
    |
    | key => class implementing App\Payments\PaymentGateway. Add Stripe, PayPal or a
    | local provider here once its gateway class exists.
    |
    */

    'payment_gateways' => [
        'manual' => ManualGateway::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Text messaging (SMS / WhatsApp)
    |--------------------------------------------------------------------------
    |
    | Driver used by the SMS and WhatsApp notification channels. "log" writes messages
    | to the application log; add a provider class implementing
    | App\Notifications\Messaging\TextMessenger to send for real.
    |
    */

    'text_messaging' => [
        'driver' => env('TEXT_MESSAGING_DRIVER', 'log'),
        'drivers' => [
            'log' => LogTextMessenger::class,
        ],
    ],

    'documents' => [
        'mimes' => ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'doc', 'docx'],
        'max_kb' => 5120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo accounts on the login page
    |--------------------------------------------------------------------------
    |
    | Shown with copy buttons on the sign-in page so people can try every role.
    | On by default only when APP_ENV=local. These accounts are created by the seeder
    | outside production only.
    |
    */

    'show_demo_accounts' => (bool) env('SHOW_DEMO_ACCOUNTS', env('APP_ENV') === 'local'),

    'demo_password' => 'password',

    'demo_accounts' => [
        ['role' => 'Super Admin', 'email' => 'admin@gymflow.test', 'hint' => 'Platform console: all gyms, users, plans'],
        ['role' => 'Gym Owner', 'email' => 'owner@gymflow.test', 'hint' => 'Full access to Iron Peak Fitness'],
        ['role' => 'Staff / Reception', 'email' => 'staff@gymflow.test', 'hint' => 'Check-in, members, payments'],
        ['role' => 'Trainer', 'email' => 'trainer@gymflow.test', 'hint' => 'Assigned members, workouts, classes'],
        ['role' => 'Member', 'email' => 'member@gymflow.test', 'hint' => 'Member portal'],
    ],

    'brand_presets' => ['#4f46e5', '#2563eb', '#0891b2', '#059669', '#65a30d', '#d97706', '#dc2626', '#db2777', '#7c3aed', '#18181b'],

];
