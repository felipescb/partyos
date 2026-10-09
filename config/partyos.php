<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Initial admin
    |--------------------------------------------------------------------------
    |
    | Used by `php artisan partyos:bootstrap` on container start. When the
    | password is empty, a random one is generated and stored once.
    |
    */

    'admin_name' => env('ADMIN_NAME', 'Admin'),

    'admin_email' => env('ADMIN_EMAIL', 'admin@partyos.local'),

    'admin_password' => env('ADMIN_PASSWORD'),

];
