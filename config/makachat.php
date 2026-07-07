<?php

return [
    'chave_servico' => env('MAKACHAT_CHAVE_SERVICO'),
    'jwt_segredo' => env('MAKACHAT_JWT_SEGREDO'),
    'api_url' => env('MAKACHAT_API_URL', 'https://makachat.hongayetu.com'),
    'socket_url' => env('MAKACHAT_SOCKET_URL', 'https://makachat.hongayetu.com'),
    'api_key' => env('MAKACHAT_API_KEY'),
    'conector_segredo' => env('MAKACHAT_CONECTOR_SEGREDO'),
    'token_ttl_segundos' => (int) env('MAKACHAT_TOKEN_TTL', 900),
];
