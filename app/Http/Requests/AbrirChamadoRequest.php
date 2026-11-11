<?php

namespace App\Http\Requests;

use App\Enums\Prioridade;
use App\Models\Chamado;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AbrirChamadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Chamado::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:120'],
            'descricao' => ['required', 'string', 'max:5000'],
            'prioridade' => ['nullable', Rule::enum(Prioridade::class)],
        ];
    }
}
