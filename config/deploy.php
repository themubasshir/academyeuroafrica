<?php

return [
    /*
    | IPs permitted to reach the one-time deploy/migrate runner. Comma-separated.
    | Read via config() so it survives `php artisan config:cache`; falls back to
    | the env file for the first post-deploy request before config is cached.
    */
    'allowed_ips' => env('DEPLOY_ALLOWED_IPS', ''),
];
