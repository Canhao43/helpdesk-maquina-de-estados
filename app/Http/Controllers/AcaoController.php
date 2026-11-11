<?php

namespace App\Http\Controllers;

use App\Enums\Acao;
use App\Http\Resources\ChamadoResource;
use App\Models\Chamado;
use App\Services\MaquinaDeEstados;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AcaoController extends Controller
{
    public function __invoke(Request $request, Chamado $chamado, Acao $acao, MaquinaDeEstados $maquina): ChamadoResource
    {
        // Antes de qualquer outra resposta: quem não vê o chamado recebe 404,
        // e não um 403 ou 422 que confirmaria que ele existe.
        Gate::authorize('view', $chamado);

        // O comentário vai cru: quem valida é a máquina, depois do estado e da
        // permissão, para que um erro de comentário nunca responda antes deles.
        $chamado = $maquina->executar($chamado, $acao, $request->user(), $request->input('comentario'));

        return new ChamadoResource($chamado->load(['solicitante', 'atendente']));
    }
}
