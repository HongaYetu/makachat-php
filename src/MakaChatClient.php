<?php

namespace Hongayetu\MakaChat;

use GuzzleHttp\Client;

/**
 * Cliente server-to-server (api_key) — depende dos endpoints /v1/s2s/* do
 * makachat-server (pendentes no servidor; ver plano M2/M5).
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

    public function mensagemSistema(string $conversaId, string $conteudo): array
    {
        return $this->post('/v1/s2s/mensagens-sistema', [
            'conversa_id' => $conversaId,
            'conteudo' => $conteudo,
        ]);
    }

    private function post(string $caminho, array $dados): array
    {
        $resposta = $this->http->post($caminho, ['json' => $dados]);

        return json_decode((string) $resposta->getBody(), true) ?? [];
    }
}
