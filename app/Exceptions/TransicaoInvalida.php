<?php

namespace App\Exceptions;

use App\Enums\Acao;
use App\Enums\EstadoChamado;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class TransicaoInvalida extends RuntimeException
{
    /**
     * @param  list<Acao>  $acoesPermitidas  o que o usuário pode fazer agora, no estado atual
     */
    public function __construct(
        public readonly Acao $acao,
        public readonly EstadoChamado $estado,
        public readonly array $acoesPermitidas,
    ) {
        parent::__construct("A ação \"{$acao->value}\" não vale para um chamado {$estado->value}.");
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'estado' => $this->estado,
            'acoes_permitidas' => $this->acoesPermitidas,
        ], 422);
    }
}
