<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Initial admin
    |--------------------------------------------------------------------------
    |
    | Used by `php artisan partyos:bootstrap` on container start. An empty
    | password falls back to "partyos".
    |
    */

    'admin_name' => env('ADMIN_NAME', 'Admin'),

    'admin_email' => env('ADMIN_EMAIL', 'admin@partyos.local'),

    'admin_password' => env('ADMIN_PASSWORD', 'partyos'),

];
