<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | O frontend agora é server-rendered (Blade + Tailwind), servido pelo
    | próprio Laravel. Não há mais SPA externo consumindo a API via
    | cross-origin, então o CORS fica no padrão restritivo do framework.
    |
    */

    'paths' => ['api/*', 'sanctum/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
