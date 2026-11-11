<?php

namespace App\Enums;

enum Papel: string
{
    case Solicitante = 'solicitante';
    case Atendente = 'atendente';
    case Admin = 'admin';

    /**
     * Quem trabalha na fila: vê todos os chamados, não só os próprios.
     */
    public function atende(): bool
    {
        return $this === self::Atendente || $this === self::Admin;
    }
}
