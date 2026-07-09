<?php

return [
    'chave_servico' => env('HONGAHUB_CHAVE_SERVICO'),
    'jwt_segredo' => env('HONGAHUB_JWT_SEGREDO'),
    'api_url' => env('HONGAHUB_API_URL', 'https://hub.hongayetu.com'),
    'socket_url' => env('HONGAHUB_SOCKET_URL', 'https://hub.hongayetu.com'),
    'api_key' => env('HONGAHUB_API_KEY'),
    'conector_segredo' => env('HONGAHUB_CONECTOR_SEGREDO'),
    'token_ttl_segundos' => (int) env('HONGAHUB_TOKEN_TTL', 900),
];
