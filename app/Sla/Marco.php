<?php

namespace App\Sla;

use App\Enums\Acao;
use App\Enums\EstadoChamado;
use Carbon\CarbonImmutable;

/**
 * Um ponto da linha do tempo do chamado: a partir de quando ele ficou em qual
 * estado, e por qual ação (nula na abertura). Sai do histórico.
 */
final readonly class Marco
{
    public function __construct(
        public CarbonImmutable $momento,
        public EstadoChamado $estado,
        public ?Acao $acao = null,
    ) {}
}
