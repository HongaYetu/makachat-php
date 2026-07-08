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

## S2S (opcional)

Autenticado com `MAKACHAT_API_KEY` (headers `X-Maka-Service` + `X-Maka-Api-Key`). O servidor também aceita, em alternativa, um JWT HS256 assinado com o `jwt_segredo` do serviço e claims `iss` + `s2s: true`.

```php
$maka = app(\Hongayetu\MakaChat\MakaChatClient::class);

// criar/obter conversa (ex.: cliente ↔ negócio com contexto de encomenda)
$maka->criarConversa([
    'tipo' => 'privada',
    'participantes' => [
        ['id_externo' => (string) $cliente->id, 'tipo' => 'cliente', 'nome' => $cliente->name],
        ['id_externo' => (string) $negocio->id, 'tipo' => 'negocio', 'nome' => $negocio->nome],
    ],
    'contexto_tipo' => 'encomenda',
    'contexto_id' => (string) $encomenda->id,
]);

// mensagem de sistema na conversa
$maka->mensagemSistema($conversaId, "Encomenda #{$encomenda->codigo} — {$encomenda->estado}");
```

## Webhook de identidades (opcional)

Quando um perfil muda no serviço (nome, foto, dados extra), empurra a atualização em batch — o chat atualiza as identidades que já existem e **ignora as desconhecidas** (nascerão pelo login ou pela primeira conversa):

```php
// ex.: observer do Negocio
$maka->atualizarIdentidades([
    ['id_externo' => (string) $negocio->id, 'tipo' => 'negocio', 'nome' => $negocio->nome, 'foto' => $negocio->logo_url],
]);
// → ['estado' => 'ok', 'atualizadas' => 1, 'ignoradas' => 0]
```

Alternativa sem webhook: configurar no painel da central a **estratégia de resolução Postgres** do serviço (consulta SQL por tipo de identidade, TTL configurável) — o servidor refresca nome/foto diretamente da base do serviço nas leituras.
