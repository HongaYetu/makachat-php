<?php

namespace Hongayetu\HongaHub;

use GuzzleHttp\Client;

/**
 * Cliente server-to-server (api_key) para os endpoints /v1/s2s/* do Honga Hub:
 * conversas (criar/fechar/reabrir), mensagens de sistema e webhook de identidades.
 */
class HongaHubClient
{
    private Client $http;

    public function __construct()
    {
        $this->http = new Client([
            'base_uri' => rtrim((string) config('honga-hub.api_url'), '/'),
            'headers' => [
                'X-Maka-Service' => (string) config('honga-hub.chave_servico'),
                'X-Maka-Api-Key' => (string) config('honga-hub.api_key'),
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
        return $this->post('/v1/s2s/chat/conversas', $dados);
    }

    /**
     * @param array{modo: string, exceto?: array<int, array{id_externo: string, tipo: string}>}|null $naoLidas
     *        conta como não lida: modo 'todos' ou 'excepto' (com a lista de excluídos)
     * @param string|null $refCliente uuid determinístico para idempotência (retries não duplicam)
     */
    public function mensagemSistema(string $conversaId, string $conteudo, ?array $naoLidas = null, ?string $refCliente = null): array
    {
        return $this->post('/v1/s2s/chat/mensagens-sistema', array_filter([
            'conversa_id' => $conversaId,
            'conteudo' => $conteudo,
            'nao_lidas' => $naoLidas,
            'ref_cliente' => $refCliente,
        ], fn ($v) => $v !== null));
    }

    /**
     * Mensagem NORMAL autorada por uma identidade do serviço (ex.: um bot com
     * nome/avatar próprios) — distinta de mensagemSistema (autor "Sistema"). A
     * identidade tem de ser participante da conversa.
     *
     * @param  array{id_externo: string, tipo: string, nome: string, foto?: string|null}  $remetente
     * @param  array{modo: string, exceto?: array<int, array{id_externo: string, tipo: string}>}|null  $naoLidas
     */
    public function mensagem(string $conversaId, array $remetente, string $conteudo, ?array $naoLidas = null, ?string $refCliente = null): array
    {
        return $this->post('/v1/s2s/chat/mensagens', array_filter([
            'conversa_id' => $conversaId,
            'remetente' => $remetente,
            'conteudo' => $conteudo,
            'nao_lidas' => $naoLidas,
            'ref_cliente' => $refCliente,
        ], fn ($v) => $v !== null));
    }

    /**
     * Marca a conversa como LIDA em nome de uma identidade do serviço (ex.: um bot
     * sempre disponível como o Agente Silva), fazendo o outro participante ver o
     * "visto". A identidade tem de ser participante da conversa.
     *
     * @param  array{id_externo: string, tipo: string}  $remetente
     */
    public function marcarLida(string $conversaId, array $remetente): array
    {
        return $this->post('/v1/s2s/chat/marcar-lida', [
            'conversa_id' => $conversaId,
            'remetente' => $remetente,
        ]);
    }

    /** Fecha a conversa: histórico visível, envio bloqueado (o motivo aparece aos participantes). */
    public function fecharConversa(string $conversaId, ?string $motivo = null): array
    {
        return $this->post('/v1/s2s/chat/conversas/fechar', array_filter([
            'conversa_id' => $conversaId,
            'motivo' => $motivo,
        ], fn ($v) => $v !== null));
    }

    public function reabrirConversa(string $conversaId): array
    {
        return $this->post('/v1/s2s/chat/conversas/reabrir', ['conversa_id' => $conversaId]);
    }

    /**
     * Fecha TODAS as conversas de um contexto (histórico visível, envio
     * bloqueado). Útil quando o serviço não guarda os conversa_id e trabalha
     * por contexto (ex.: sócia concluída → grupo + 1:1 read-only). Idempotente.
     */
    public function fecharPorContexto(string $contextoTipo, string $contextoId, ?string $motivo = null): array
    {
        return $this->post('/v1/s2s/chat/conversas/fechar-por-contexto', array_filter([
            'contexto_tipo' => $contextoTipo,
            'contexto_id' => $contextoId,
            'motivo' => $motivo,
        ], fn ($v) => $v !== null));
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

    /**
     * Presença em lote: estado das ligações ao chat para os alvos indicados
     * (máx. 200 por chamada). O serviço decide QUEM perguntar (o grafo social
     * é dele); identidades com `metadados.ocultar_online` vêm sempre offline.
     *
     * @param array<int, array{id_externo: string, tipo: string}> $alvos
     * @return array{estado: string, presencas: array<int, array{id_externo: string, tipo: string, online: bool, ultimo_visto_em: ?string}>}
     */
    public function presencaLote(array $alvos): array
    {
        return $this->post('/v1/s2s/presenca/lote', ['alvos' => array_values($alvos)]);
    }

    /**
     * Barramento de eventos do Honga Hub: empurra um evento para os clientes
     * ligados (fan-out). Alternativa HTTP ao Redis pub/sub — para a via rápida
     * publica diretamente em `hub:emit:{chave_servico}` com o teu Redis.
     *
     * @param array{tipo: string, ids?: array<int, string>} $alvo
     */
    public function emitirEvento(array $alvo, string $evento, mixed $payload = null, ?string $namespace = null): array
    {
        return $this->post('/v1/eventos/emitir', array_filter([
            'alvo' => $alvo,
            'evento' => $evento,
            'payload' => $payload,
            'namespace' => $namespace,
        ], fn ($v) => $v !== null));
    }

    private function post(string $caminho, array $dados): array
    {
        $resposta = $this->http->post($caminho, ['json' => $dados]);

        return json_decode((string) $resposta->getBody(), true) ?? [];
    }
}
