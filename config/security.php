<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | CIDR list (comma-separated) or "*" of reverse proxies whose
    | X-Forwarded-* headers may be honored. Required in production —
    | AppServiceProvider::boot() throws if APP_ENV=production and this
    | resolves to null/empty.
    |
    */

    'trusted_proxies' => env('TRUSTED_PROXIES'),

];
