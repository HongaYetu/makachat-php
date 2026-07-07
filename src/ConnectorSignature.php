<?php

namespace Hongayetu\MakaChat;

use Illuminate\Http\Request;

class ConnectorSignature
{
    /**
     * Verifica a assinatura HMAC dos pedidos do MakaChat aos endpoints de
     * contexto do serviço (X-Maka-Timestamp + X-Maka-Signature).
     */
    public static function verify(Request $request, int $toleranciaSegundos = 300): bool
    {
        $timestamp = (string) $request->header('X-Maka-Timestamp');
        $assinatura = (string) $request->header('X-Maka-Signature');
        $segredo = (string) config('makachat.conector_segredo');

        if ($timestamp === '' || $assinatura === '' || $segredo === '') {
            return false;
        }

        if (abs(time() - (int) $timestamp) > $toleranciaSegundos) {
            return false;
        }

        $esperada = hash_hmac('sha256', $timestamp."\n".$request->getPathInfo(), $segredo);

        return hash_equals($esperada, $assinatura);
    }
}
