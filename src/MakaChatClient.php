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
