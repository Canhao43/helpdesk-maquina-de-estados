<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class ChamadoMudouNoMeioDoCaminho extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('O chamado mudou enquanto a ação era executada. Consulte de novo antes de tentar outra vez.');
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }
}
