<?php

namespace App\Enums;

/**
 * Os prazos de SLA moram aqui, em minutos corridos: mudam com deploy, ficam
 * no mesmo lugar que a prioridade e entram no teste como estão.
 */
enum Prioridade: string
{
    case Baixa = 'baixa';
    case Media = 'media';
    case Alta = 'alta';
    case Critica = 'critica';

    public function minutosParaPrimeiraResposta(): int
    {
        return match ($this) {
            self::Critica => 30,
            self::Alta => 2 * 60,
            self::Media => 8 * 60,
            self::Baixa => 24 * 60,
        };
    }

    public function minutosParaResolucao(): int
    {
        return match ($this) {
            self::Critica => 4 * 60,
            self::Alta => 8 * 60,
            self::Media => 24 * 60,
            self::Baixa => 72 * 60,
        };
    }
}
