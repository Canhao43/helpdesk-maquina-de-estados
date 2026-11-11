<?php

namespace Database\Factories;

use App\Enums\EstadoChamado;
use App\Enums\Prioridade;
use App\Models\Chamado;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chamado>
 */
class ChamadoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'solicitante_id' => User::factory(),
            'titulo' => fake()->sentence(4),
            'descricao' => fake()->paragraph(),
            'prioridade' => fake()->randomElement(Prioridade::cases()),
            'estado' => EstadoChamado::Aberto,
        ];
    }
}
