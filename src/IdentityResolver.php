<?php

namespace Hongayetu\HongaHub;

use Illuminate\Http\Request;

interface IdentityResolver
{
    /**
     * Resolve a identidade de chat a partir da sessão autenticada do serviço.
     * Devolver null resulta em 401.
     *
     * @return array{id: string, tipo: string, nome: string, foto?: string|null, honga_user_id?: int|null}|null
     */
    public function resolve(Request $request): ?array;
}
