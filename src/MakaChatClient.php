<?php

namespace Hongayetu\MakaChat;

use GuzzleHttp\Client;

/**
 * Cliente server-to-server (api_key) para os endpoints /v1/s2s/* do makachat-server:
 * conversas (criar/fechar/reabrir), mensagens de sistema e webhook de identidades.
 */
class MakaChatClient
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => rtrim((string) config('makachat.api_url'), '/'),
            'headers' => [
                'X-Maka-Service' => (string) config('makachat.chave_servico'),
                'X-Maka-Api-Key' => (string) config('makachat.api_key'),
                'Accept' => 'application/json',
            ],
            'timeout' => 10,
        ]);
    }

    /**
     * @param array{tipo: string, participantes: array<int, array{id_externo: string, tipo: string, nome?: string}>, titulo?: string, contexto_tipo?: string, contexto_id?: string} $dados
     */
    public function criarConversa(array $dados): array
    {
        return $this->post('/v1/s2s/conversas', $dados);
    }

    /**
     * @param array{modo: string, exceto?: array<int, array{id_externo: string, tipo: string}>}|null $naoLidas
     *        conta como não lida: modo 'todos' ou 'excepto' (com a lista de excluídos)
     * @param string|null $refCliente uuid determinístico para idempotência (retries não duplicam)
     */
    public function mensagemSistema(string $conversaId, string $conteudo, ?array $naoLidas = null, ?string $refCliente = null): array
    {
        return $this->post('/v1/s2s/mensagens-sistema', array_filter([
            'conversa_id' => $conversaId,
            'conteudo' => $conteudo,
            'nao_lidas' => $naoLidas,
            'ref_cliente' => $refCliente,
        ], fn ($v) => $v !== null));
    }

    /** Fecha a conversa: histórico visível, envio bloqueado (o motivo aparece aos participantes). */
    public function fecharConversa(string $conversaId, ?string $motivo = null): array
    {
        return $this->post('/v1/s2s/conversas/fechar', array_filter([
            'conversa_id' => $conversaId,
            'motivo' => $motivo,
        ], fn ($v) => $v !== null));
    }

    public function reabrirConversa(string $conversaId): array
    {
        return $this->post('/v1/s2s/conversas/reabrir', ['conversa_id' => $conversaId]);
    }

    /**
     * Webhook de identidades (batch): empurra nome/foto/metadados quando o
     * perfil muda no serviço. Atualiza as que existem no chat; as que não
     * existem são ignoradas pelo servidor (podem ainda não ter conversas).
     *
     * @param array<int, array{id_externo: string, tipo: string, nome?: string, foto?: ?string, metadados?: array<string, mixed>}> $identidades
     * @return array{estado: string, atualizadas: int, ignoradas: int}
     */
    public function atualizarIdentidades(array $identidades): array
    {
        return $this->post('/v1/s2s/identidades', ['identidades' => array_values($identidades)]);
    }

    private function post(string $caminho, array $dados): array
    {
        $resposta = $this->http->post($caminho, ['json' => $dados]);

        return json_decode((string) $resposta->getBody(), true) ?? [];
    }
}
