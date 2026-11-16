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

    /*
    |--------------------------------------------------------------------------
    | Administrator
    |--------------------------------------------------------------------------
    |
    | Google account email of whoever administers the shop. The user seeder
    | creates that account with the admin role, and it is linked to the Google
    | login the first time the person signs in.
    |
    */

    'admin_email' => env('SHOP_ADMIN_EMAIL', 'admin@elenor.test'),

    /*
    |--------------------------------------------------------------------------
    | Shipping
    |--------------------------------------------------------------------------
    |
    | Elenor is online only: orders are shipped. Deliveries go to the NOA
    | provinces listed here, each with its cost. A null cost means "to be
    | agreed": the order is placed without it and the store tells the customer
    | the cost before shipping. Zero means free shipping.
    |
    */

    'shipping' => [
        'zones' => [
            'Tucumán' => env('SHOP_SHIPPING_COST_TUCUMAN'),
            'Salta' => env('SHOP_SHIPPING_COST_NOA'),
            'Jujuy' => env('SHOP_SHIPPING_COST_NOA'),
            'Catamarca' => env('SHOP_SHIPPING_COST_NOA'),
            'Santiago del Estero' => env('SHOP_SHIPPING_COST_NOA'),
            'La Rioja' => env('SHOP_SHIPPING_COST_NOA'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pickup
    |--------------------------------------------------------------------------
    |
    | Off while there is no shop to pick up from. When it is turned on, the
    | checkout offers pickup (free) and cash payment on pickup.
    |
    */

    'pickup' => [
        'enabled' => (bool) env('SHOP_PICKUP_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Bank transfer
    |--------------------------------------------------------------------------
    |
    | Account the customer transfers to. Shown after placing an order and in
    | the confirmation email.
    |
    */

    'bank' => [
        'holder' => env('SHOP_BANK_HOLDER'),
        'bank' => env('SHOP_BANK_NAME'),
        'cbu' => env('SHOP_BANK_CBU'),
        'alias' => env('SHOP_BANK_ALIAS'),
        'cuit' => env('SHOP_BANK_CUIT'),
    ],

];
