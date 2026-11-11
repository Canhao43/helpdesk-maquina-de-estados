<?php

namespace Database\Seeders;

use App\Models\Chamado;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Três usuários de demonstração, um por papel, todos com a senha
     * "password". Em produção isso seria uma porta aberta com senha
     * conhecida, então o seeder se recusa a rodar lá.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Os dados de demonstração não rodam em produção.');
        }

        $solicitante = User::factory()->create(['name' => 'Solicitante de Demonstração', 'email' => 'solicitante@exemplo.test']);
        User::factory()->atendente()->create(['name' => 'Atendente de Demonstração', 'email' => 'atendente@exemplo.test']);
        User::factory()->admin()->create(['name' => 'Admin de Demonstração', 'email' => 'admin@exemplo.test']);

        Chamado::factory()->count(3)->for($solicitante, 'solicitante')->create();
        Chamado::factory()->count(5)->create();
    }
}
