<?php

namespace App\Http\Controllers;

use App\Http\Requests\AbrirChamadoRequest;
use App\Http\Resources\ChamadoResource;
use App\Http\Resources\EventoResource;
use App\Models\Chamado;
use App\Services\MaquinaDeEstados;
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
            ->with(['solicitante', 'atendente', 'eventos'])
            ->latest('id')
            ->paginate(20);

        return ChamadoResource::collection($chamados);
    }

    public function store(AbrirChamadoRequest $request, MaquinaDeEstados $maquina): JsonResponse
    {
        $chamado = $maquina->abrir($request->validated(), $request->user());

        return (new ChamadoResource($chamado->load(['solicitante', 'atendente', 'eventos'])))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('chamados.show', $chamado));
    }

    public function show(Chamado $chamado): ChamadoResource
    {
        Gate::authorize('view', $chamado);

        return new ChamadoResource($chamado->load(['solicitante', 'atendente', 'eventos']));
    }

    public function historico(Chamado $chamado): AnonymousResourceCollection
    {
        Gate::authorize('view', $chamado);

        return EventoResource::collection($chamado->eventos()->with('autor')->get());
    }
}
