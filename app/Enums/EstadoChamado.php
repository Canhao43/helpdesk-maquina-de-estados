<?php

namespace App\Enums;

enum EstadoChamado: string
{
    case Aberto = 'aberto';
    case EmAtendimento = 'em_atendimento';
    case AguardandoSolicitante = 'aguardando_solicitante';
    case Resolvido = 'resolvido';
    case Fechado = 'fechado';
    case Cancelado = 'cancelado';

    /**
     * Estado final não tem saída: nenhuma ação parte dele.
     */
    public function final(): bool
    {
        return $this === self::Fechado || $this === self::Cancelado;
    }
}
