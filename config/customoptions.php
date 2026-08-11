<?php
/**
 * Created by PhpStorm.
 * User: Gerrit
 * Date: 29/10/2020
 * Time: 12:11
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */
    'buttons_on_page' =>env('NR_CATEGORY_BUTTONS',6),


    'eatin' => env('EATIN',true),
    'takeaway' => env('TAKE_AWAY', false),
    'delivery' => env('DELIVERY',false),

    'eatin_prepay'=> env('EATIN_PREPAY',false),
    'takeaway_prepay'=> env('TAKEAWAY_PREPAY',true),
    'delivery_prepay'=> env('DELIVERY_PREPAY',true),

    'clean_table_after_order' => env('CLEAN_TABLE_AFTER_ORDER',false),
    'clean_table_after_bill' => env('CLEAN_TABLE_AFTER_BILL',false),

    // Which printer number the kitchen display shows (products routed to this
    // printer are the ones cooked in the kitchen). Comma-separated for several.
    'kitchen_printers' => env('KITCHEN_PRINTERS', '2'),

    // Station displays (kitchen-style boards), each filtered to its printer(s).
    // 'label' is the on-screen name; 'printers' is comma-separated printto value(s).
    'stations' => [
        'cocina' => ['label' => 'Cocina', 'printers' => env('KITCHEN_PRINTERS', '2')],
        'bar' => ['label' => 'Bar', 'printers' => env('BAR_PRINTERS', '1')],
        'cocktails' => ['label' => 'Cocktails', 'printers' => env('COCKTAIL_PRINTERS', '3')],
    ],

    // Role granted to self-registered users. On non-production (test/staging)
    // this defaults to 'manager' so employees can register and test features;
    // on production it is empty (no access) unless REGISTER_DEFAULT_ROLE is set.
    'register_default_role' => env('REGISTER_DEFAULT_ROLE', env('APP_ENV') === 'production' ? '' : 'manager'),

    ];
