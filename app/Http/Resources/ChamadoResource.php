<?php

namespace App\Http\Resources;

use App\Models\Chamado;
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
            'solicitante' => ['id' => $this->solicitante->id, 'nome' => $this->solicitante->name],
            'atendente' => $this->atendente ? ['id' => $this->atendente->id, 'nome' => $this->atendente->name] : null,
            'criado_em' => $this->created_at?->toIso8601String(),
            'atualizado_em' => $this->updated_at?->toIso8601String(),
        ];
    }
}
