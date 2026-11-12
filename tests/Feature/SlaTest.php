<?php

namespace Tests\Feature;

use App\Models\Chamado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SlaTest extends TestCase
{
    use RefreshDatabase;

    private function como(User $usuario): static
    {
        Sanctum::actingAs($usuario, ['*']);

        return $this;
    }

    private function agir(User $quem, int $id, string $acao, string $comentario = 'Teste.'): TestResponse
    {
        return $this->como($quem)->postJson("/api/chamados/{$id}/acoes/{$acao}", ['comentario' => $comentario])->assertOk();
    }

    public function test_o_sla_segue_o_historico_e_para_enquanto_se_espera_o_solicitante(): void
    {
        $this->travelTo('2026-11-10 09:00:00');
        $solicitante = User::factory()->create();
        $atendente = User::factory()->atendente()->create();

        $id = $this->como($solicitante)
            ->postJson('/api/chamados', ['titulo' => 'VPN cai', 'descricao' => 'A cada 10 minutos.', 'prioridade' => 'media'])
            ->assertCreated()
            ->assertJsonPath('data.sla.resolucao.limite_minutos', 24 * 60)
            ->assertJsonPath('data.sla.primeira_resposta.prazo_em', '2026-11-10T17:00:00+00:00')
            ->json('data.id');

        $this->travel(60)->minutes();
        $this->agir($atendente, $id, 'assumir')
            ->assertJsonPath('data.sla.primeira_resposta.respondida_em', '2026-11-10T10:00:00+00:00')
            ->assertJsonPath('data.sla.primeira_resposta.estourado', false);

        $this->travel(60)->minutes();
        $this->agir($atendente, $id, 'pedir_informacao', 'Qual o sistema operacional?');

        // Cinco horas com a bola do lado do solicitante.
        $this->travel(5)->hours();
        $this->como($solicitante)->getJson("/api/chamados/{$id}")
            ->assertJsonPath('data.sla.resolucao.consumido_minutos', 120)
            ->assertJsonPath('data.sla.resolucao.relogio', 'pausado')
            ->assertJsonPath('data.sla.resolucao.vence_em', null);

        $this->agir($solicitante, $id, 'responder', 'Windows 11.');
        $this->travel(30)->minutes();

        // 24 h de limite, 2 h 30 consumidas: vence 21 h 30 depois de agora.
        $this->como($atendente)->getJson("/api/chamados/{$id}")
            ->assertJsonPath('data.sla.resolucao.consumido_minutos', 150)
            ->assertJsonPath('data.sla.resolucao.relogio', 'correndo')
            ->assertJsonPath('data.sla.resolucao.vence_em', now()->addMinutes(24 * 60 - 150)->toIso8601String())
            ->assertJsonPath('data.sla.resolucao.estourado', false);
    }

    public function test_critico_parado_na_fila_aparece_estourado_na_listagem(): void
    {
        $this->travelTo('2026-11-10 09:00:00');
        $solicitante = User::factory()->create();
        $this->como($solicitante)->postJson('/api/chamados', ['titulo' => 'Servidor fora', 'descricao' => 'Ninguém acessa.', 'prioridade' => 'critica']);

        $this->travel(45)->minutes();

        $this->como(User::factory()->atendente()->create())->getJson('/api/chamados')
            ->assertJsonPath('data.0.sla.primeira_resposta.estourado', true)
            ->assertJsonPath('data.0.sla.resolucao.estourado', false);
    }

    public function test_chamado_sem_historico_nao_tem_sla(): void
    {
        $chamado = Chamado::factory()->create();

        $this->como($chamado->solicitante)->getJson("/api/chamados/{$chamado->id}")->assertJsonPath('data.sla', null);
    }

    public function test_a_listagem_nao_faz_uma_consulta_por_chamado(): void
    {
        $solicitante = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->como($solicitante)->postJson('/api/chamados', ['titulo' => "Chamado {$i}", 'descricao' => 'x']);
        }

        $consultas = 0;
        DB::listen(function () use (&$consultas) {
            $consultas++;
        });

        $this->como($solicitante)->getJson('/api/chamados')->assertOk()->assertJsonCount(5, 'data');

        // Contagem da paginação, chamados, solicitantes, atendentes e eventos:
        // o número não cresce com a quantidade de chamados.
        $this->assertLessThanOrEqual(6, $consultas);
    }
}
