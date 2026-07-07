# hongayetu/makachat-php

SDK MakaChat para os serviços Laravel do ecossistema (Humbi, Escola Inteligente, Socia, Kanda...). Não depende do auth-sdk — o chat é ortogonal ao SSO.

## Instalação

```bash
composer require hongayetu/makachat-php
php artisan vendor:publish --tag=makachat-config
```

`.env` do serviço (valores gerados no painel MakaChat da central):

```
MAKACHAT_CHAVE_SERVICO=humbi
MAKACHAT_JWT_SEGREDO=...
MAKACHAT_API_URL=https://makachat.hongayetu.com
MAKACHAT_SOCKET_URL=https://makachat.hongayetu.com
MAKACHAT_CONECTOR_SEGREDO=...   # se o serviço expõe context cards
MAKACHAT_API_KEY=...            # se o serviço usa s2s
```

## Endpoint de token (o único passo obrigatório)

```php
// app/MakaChat/ClienteResolver.php
class ClienteResolver implements \Hongayetu\MakaChat\IdentityResolver
{
    public function resolve(Request $request): ?array
    {
        $user = $request->user();

        return $user ? [
            'id' => (string) $user->id,
            'tipo' => 'cliente',
            'nome' => $user->name,
            'foto' => $user->foto_link(),
            'honga_user_id' => $user->honga_user_id,
        ] : null;
    }
}

// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::makachatToken('/makachat/token', \App\MakaChat\ClienteResolver::class);
});
```

As apps passam `getToken: () => req('/api/makachat/token')` ao `<MakaChatProvider>` e está feito.

## Context cards (opcional)

```php
Route::get('/makachat/context/corrida/{id}', function (Request $request, $id) {
    abort_unless(\Hongayetu\MakaChat\ConnectorSignature::verify($request), 401);

    $corrida = Corrida::findOrFail($id);

    return response()->json([
        'titulo' => "Corrida #{$corrida->id}",
        'subtitulo' => $corrida->destino,
        'foto_url' => null,
        'linhas' => ["Estado: {$corrida->estado}"],
        'acoes' => [],
    ]);
});
```

## S2S (opcional; requer endpoints /v1/s2s/* no servidor)

```php
app(\Hongayetu\MakaChat\MakaChatClient::class)->criarConversa([...]);
```
