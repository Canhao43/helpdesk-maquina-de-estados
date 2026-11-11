<?php

namespace App\Sla;

use Carbon\CarbonImmutable;

final readonly class Sla
{
    /**
     * @param  'correndo'|'pausado'|'parado'  $relogio
     */
    public function __construct(
        public ?PrimeiraResposta $primeiraResposta,
        public int $limiteDaResolucaoEmMinutos,
        public int $consumidoEmMinutos,
        public string $relogio,
        public ?CarbonImmutable $resolucaoVenceEm,
        public bool $resolucaoEstourada,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function paraArray(): array
    {
        return [
            'primeira_resposta' => $this->primeiraResposta?->paraArray(),
            'resolucao' => [
                'limite_minutos' => $this->limiteDaResolucaoEmMinutos,
                'consumido_minutos' => $this->consumidoEmMinutos,
                'relogio' => $this->relogio,
                'vence_em' => $this->resolucaoVenceEm?->toIso8601String(),
                'estourado' => $this->resolucaoEstourada,
            ],
        ];
    }
}
