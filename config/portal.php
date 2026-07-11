<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Portal Property
    |--------------------------------------------------------------------------
    |
    | The guest portal is single-property for now. Set PORTAL_PROPERTY_ID to a
    | specific property, or leave null to use the first active property.
    |
    */

    'property_id' => env('PORTAL_PROPERTY_ID'),

    /*
    |--------------------------------------------------------------------------
    | Tax Rate
    |--------------------------------------------------------------------------
    */

    'tax_rate' => (float) env('PORTAL_TAX_RATE', 10),

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    */

    'locales' => ['en'],

    'default_locale' => env('PORTAL_LOCALE', 'en'),

];
