<?php

namespace Hongayetu\MakaChat;

use Firebase\JWT\JWT;
use Illuminate\Support\Str;

class TokenIssuer
{
    /**
     * Emite o JWT de sessão de chat (HS256, TTL curto) para a identidade do
     * serviço. Ver PROTOCOL.md do makachat-server.
     */
    public function issue(
        string $externalId,
        string $tipo,
        string $nome,
        ?string $foto = null,
        ?int $hongaUserId = null,
        ?array $metadados = null,
    ): string {
        $agora = time();

        return JWT::encode(array_filter([
            'iss' => config('makachat.chave_servico'),
            'sub' => $externalId,
            'tipo' => $tipo,
            'nome' => $nome,
            'foto' => $foto,
            'honga_user_id' => $hongaUserId,
            // extras do serviço (convenção: verificado, username, ocultar_online...)
            'metadados' => $metadados,
            'jti' => (string) Str::uuid(),
            'iat' => $agora,
            'exp' => $agora + (int) config('makachat.token_ttl_segundos', 900),
        ], fn ($v) => $v !== null), (string) config('makachat.jwt_segredo'), 'HS256');
    }

    /**
     * Resposta padrão do endpoint de token, no formato que os pacotes cliente
     * (@hongayetu/makachat-*) esperam do getToken().
     *
     * @return array{estado: string, token: string, socket_url: string, api_url: string}
     */
    public function resposta(
        string $externalId,
        string $tipo,
        string $nome,
        ?string $foto = null,
        ?int $hongaUserId = null,
        ?array $metadados = null,
    ): array {
        return [
            'estado' => 'ok',
            'token' => $this->issue($externalId, $tipo, $nome, $foto, $hongaUserId, $metadados),
            'socket_url' => (string) config('makachat.socket_url'),
            'api_url' => (string) config('makachat.api_url'),
        ];
    }
}
