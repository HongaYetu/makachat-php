<?php

namespace Hongayetu\MakaChat;

/**
 * Decide se `de` pode contactar `para` (bloqueios, privacidade, restrições).
 * Cada lado é ['tipo' => string, 'id' => string]. Devolve true/false ou
 * ['permitido' => bool, 'motivo' => ?string] para dar contexto ao utilizador.
 */
interface AutorizacaoResolver
{
    public function resolve(array $de, array $para): bool|array;
}
