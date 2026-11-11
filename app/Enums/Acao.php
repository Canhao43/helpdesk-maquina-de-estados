<?php

namespace App\Enums;

/**
 * A tabela de transições do chamado. Cada ação diz de quais estados parte e
 * para qual leva; quem pode executá-la fica na ChamadoPolicy. Uma mudança de
 * estado que não esteja aqui não existe.
 */
enum Acao: string
{
    case Assumir = 'assumir';
    case DevolverAFila = 'devolver_a_fila';
    case PedirInformacao = 'pedir_informacao';
    case Responder = 'responder';
    case Resolver = 'resolver';
    case Reabrir = 'reabrir';
    case Fechar = 'fechar';
    case Cancelar = 'cancelar';

    /**
     * @return list<EstadoChamado>
     */
    public function origens(): array
    {
        return match ($this) {
            self::Assumir => [EstadoChamado::Aberto],
            self::DevolverAFila, self::PedirInformacao, self::Resolver => [EstadoChamado::EmAtendimento],
            self::Responder => [EstadoChamado::AguardandoSolicitante],
            self::Reabrir, self::Fechar => [EstadoChamado::Resolvido],
            self::Cancelar => [EstadoChamado::Aberto, EstadoChamado::AguardandoSolicitante],
        };
    }

    public function destino(): EstadoChamado
    {
        return match ($this) {
            self::Assumir, self::Responder, self::Reabrir => EstadoChamado::EmAtendimento,
            self::DevolverAFila => EstadoChamado::Aberto,
            self::PedirInformacao => EstadoChamado::AguardandoSolicitante,
            self::Resolver => EstadoChamado::Resolvido,
            self::Fechar => EstadoChamado::Fechado,
            self::Cancelar => EstadoChamado::Cancelado,
        };
    }

    public function parteDe(EstadoChamado $estado): bool
    {
        return in_array($estado, $this->origens(), true);
    }

    /**
     * Ações que mudam a conversa precisam dizer por quê: o que falta, qual a
     * resposta, qual a solução, por que voltou. Assumir, fechar e cancelar
     * se explicam sozinhas.
     */
    public function exigeComentario(): bool
    {
        return match ($this) {
            self::DevolverAFila, self::PedirInformacao, self::Responder, self::Resolver, self::Reabrir => true,
            self::Assumir, self::Fechar, self::Cancelar => false,
        };
    }
}
