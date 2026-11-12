<?php

namespace App\Sla;

use Carbon\CarbonImmutable;

final readonly class PrimeiraResposta
{
    public function __construct(
        public int $limiteEmMinutos,
        public CarbonImmutable $prazo,
        public ?CarbonImmutable $respondidaEm,
        public bool $estourado,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function paraArray(): array
    {
        return [
            'limite_minutos' => $this->limiteEmMinutos,
            'prazo_em' => $this->prazo->toIso8601String(),
            'respondida_em' => $this->respondidaEm?->toIso8601String(),
            'estourado' => $this->estourado,
        ];
    }
}
