<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | Prices are stored as plain decimals in this currency and displayed with
    | the Argentine convention: "$ 12.345,67" (dot for thousands, comma for
    | decimals). See App\Support\Money and the money() helper.
    |
    */

    'currency' => [
        'code' => env('SHOP_CURRENCY', 'ARS'),
        'symbol' => '$',
        'decimals' => 2,
        'decimal_separator' => ',',
        'thousands_separator' => '.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Contact
    |--------------------------------------------------------------------------
    |
    | Store contact details shown in the footer and used to build the
    | WhatsApp links. Empty values are simply not rendered.
    |
    */

    'contact' => [
        'address' => env('SHOP_ADDRESS'),
        'phone' => env('SHOP_PHONE'),
        'whatsapp' => env('SHOP_WHATSAPP'),
        'email' => env('SHOP_EMAIL'),
        'hours' => env('SHOP_HOURS'),
    ],

];
