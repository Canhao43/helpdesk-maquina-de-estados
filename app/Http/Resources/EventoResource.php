<?php

namespace App\Http\Resources;

use App\Models\EventoDoChamado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin EventoDoChamado
 */
class EventoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'acao' => $this->acao?->value ?? 'abrir',
            'estado_anterior' => $this->estado_anterior,
            'estado_novo' => $this->estado_novo,
            'comentario' => $this->comentario,
            'autor' => ['id' => $this->autor->id, 'nome' => $this->autor->name],
            'em' => $this->created_at?->toIso8601String(),
        ];
    }
}
