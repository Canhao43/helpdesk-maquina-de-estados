<?php

namespace App\Http\Resources;

use App\Models\Chamado;
use App\Services\MaquinaDeEstados;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Chamado
 */
class ChamadoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'descricao' => $this->descricao,
            'prioridade' => $this->prioridade,
            'estado' => $this->estado,
            'versao' => $this->versao,
            // O cliente não precisa reimplementar a máquina de estados para
            // saber quais botões mostrar: a API diz o que vale para quem pergunta.
            'acoes_disponiveis' => app(MaquinaDeEstados::class)->acoesDisponiveis($this->resource, $request->user()),
            'solicitante' => ['id' => $this->solicitante->id, 'nome' => $this->solicitante->name],
            'atendente' => $this->atendente ? ['id' => $this->atendente->id, 'nome' => $this->atendente->name] : null,
            'criado_em' => $this->created_at?->toIso8601String(),
            'atualizado_em' => $this->updated_at?->toIso8601String(),
        ];
    }
}
