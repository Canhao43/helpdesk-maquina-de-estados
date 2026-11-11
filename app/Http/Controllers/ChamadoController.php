<?php

namespace App\Http\Controllers;

use App\Enums\EstadoChamado;
use App\Enums\Prioridade;
use App\Http\Requests\AbrirChamadoRequest;
use App\Http\Resources\ChamadoResource;
use App\Models\Chamado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ChamadoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $chamados = Chamado::query()
            ->visiveisPara($request->user())
            ->with(['solicitante', 'atendente'])
            ->latest('id')
            ->paginate(20);

        return ChamadoResource::collection($chamados);
    }

    public function store(AbrirChamadoRequest $request): JsonResponse
    {
        $chamado = new Chamado($request->validated());
        $chamado->prioridade ??= Prioridade::Media;
        $chamado->estado = EstadoChamado::Aberto;
        $chamado->solicitante()->associate($request->user());
        $chamado->save();

        return (new ChamadoResource($chamado->load(['solicitante', 'atendente'])))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('chamados.show', $chamado));
    }

    public function show(Chamado $chamado): ChamadoResource
    {
        Gate::authorize('view', $chamado);

        return new ChamadoResource($chamado->load(['solicitante', 'atendente']));
    }
}
