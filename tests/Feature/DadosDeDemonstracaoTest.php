<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DadosDeDemonstracaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_um_usuario_por_papel_fora_de_producao(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('users', ['email' => 'solicitante@exemplo.test', 'papel' => 'solicitante']);
        $this->assertDatabaseHas('users', ['email' => 'atendente@exemplo.test', 'papel' => 'atendente']);
        $this->assertDatabaseHas('users', ['email' => 'admin@exemplo.test', 'papel' => 'admin']);
    }

    public function test_se_recusa_a_rodar_em_producao(): void
    {
        $this->app['env'] = 'production';

        // Sem --force, o db:seed já pede confirmação em produção e para. O
        // risco real é o --force num script de deploy, e é ele que se testa.
        $this->assertThrows(
            fn () => $this->artisan('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true])->run(),
            RuntimeException::class,
            'produção',
        );
        $this->assertDatabaseCount('users', 0);
    }
}
