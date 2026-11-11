<?php

namespace Tests\Feature;

use App\Enums\EstadoChamado;
use App\Models\Chamado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MaquinaDeEstadosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sempre com comentário: aqui o assunto são as regras de estado e de
     * permissão. A obrigatoriedade do comentário é testada no HistoricoTest.
     */
    private function agir(User $quem, Chamado $chamado, string $acao): TestResponse
    {
        Sanctum::actingAs($quem);

        return $this->postJson("/api/chamados/{$chamado->id}/acoes/{$acao}", ['comentario' => 'Registrado pelo teste.']);
    }

    private function emAtendimentoPor(User $atendente): Chamado
    {
        return Chamado::factory()->create([
            'estado' => EstadoChamado::EmAtendimento,
            'atendente_id' => $atendente->id,
        ]);
    }

    public function test_caminho_completo_do_chamado(): void
    {
        $chamado = Chamado::factory()->create();
        $solicitante = $chamado->solicitante;
        $atendente = User::factory()->atendente()->create();

        $this->agir($atendente, $chamado, 'assumir')->assertOk()
            ->assertJsonPath('data.estado', 'em_atendimento')
            ->assertJsonPath('data.atendente.id', $atendente->id);
        $this->agir($atendente, $chamado, 'pedir_informacao')->assertOk()->assertJsonPath('data.estado', 'aguardando_solicitante');
        $this->agir($solicitante, $chamado, 'responder')->assertOk()->assertJsonPath('data.estado', 'em_atendimento');
        $this->agir($atendente, $chamado, 'resolver')->assertOk()->assertJsonPath('data.estado', 'resolvido');
        $this->agir($solicitante, $chamado, 'fechar')->assertOk()
            ->assertJsonPath('data.estado', 'fechado')
            ->assertJsonPath('data.versao', 5)
            ->assertJsonPath('data.acoes_disponiveis', []);
    }

    public function test_reabrir_volta_para_o_mesmo_atendente(): void
    {
        $atendente = User::factory()->atendente()->create();
        $chamado = Chamado::factory()->create(['estado' => EstadoChamado::Resolvido, 'atendente_id' => $atendente->id]);

        $this->agir($chamado->solicitante, $chamado, 'reabrir')->assertOk()
            ->assertJsonPath('data.estado', 'em_atendimento')
            ->assertJsonPath('data.atendente.id', $atendente->id);
    }

    public function test_devolver_a_fila_solta_o_atendente(): void
    {
        $atendente = User::factory()->atendente()->create();
        $chamado = $this->emAtendimentoPor($atendente);

        $this->agir($atendente, $chamado, 'devolver_a_fila')->assertOk()
            ->assertJsonPath('data.estado', 'aberto')
            ->assertJsonPath('data.atendente', null);
    }

    public function test_acao_que_nao_vale_no_estado_responde_422_com_o_que_vale(): void
    {
        $chamado = Chamado::factory()->create();
        $atendente = User::factory()->atendente()->create();

        $this->agir($atendente, $chamado, 'resolver')
            ->assertUnprocessable()
            ->assertJsonPath('estado', 'aberto')
            ->assertJsonPath('acoes_permitidas', ['assumir']);

        $this->assertSame(EstadoChamado::Aberto, $chamado->fresh()->estado);
        $this->assertSame(0, $chamado->fresh()->versao);
    }

    public function test_estado_final_nao_aceita_nada(): void
    {
        $chamado = Chamado::factory()->create(['estado' => EstadoChamado::Fechado]);

        $this->agir($chamado->solicitante, $chamado, 'reabrir')
            ->assertUnprocessable()
            ->assertJsonPath('acoes_permitidas', []);
    }

    public function test_solicitante_nao_assume_403(): void
    {
        $chamado = Chamado::factory()->create();

        $this->agir($chamado->solicitante, $chamado, 'assumir')->assertForbidden();
        $this->assertSame(EstadoChamado::Aberto, $chamado->fresh()->estado);
    }

    public function test_atendente_nao_mexe_no_chamado_de_outro_atendente(): void
    {
        $ana = User::factory()->atendente()->create();
        $bia = User::factory()->atendente()->create();
        $chamado = $this->emAtendimentoPor($ana);

        foreach (['resolver', 'pedir_informacao', 'devolver_a_fila'] as $acao) {
            $this->agir($bia, $chamado, $acao)->assertForbidden();
        }

        $this->assertSame(EstadoChamado::EmAtendimento, $chamado->fresh()->estado);
    }

    public function test_admin_resolve_chamado_de_qualquer_atendente(): void
    {
        $chamado = $this->emAtendimentoPor(User::factory()->atendente()->create());

        $this->agir(User::factory()->admin()->create(), $chamado, 'resolver')->assertOk()
            ->assertJsonPath('data.estado', 'resolvido');
    }

    public function test_atendente_nao_fecha_nem_reabre_pelo_solicitante(): void
    {
        $atendente = User::factory()->atendente()->create();
        $chamado = Chamado::factory()->create(['estado' => EstadoChamado::Resolvido, 'atendente_id' => $atendente->id]);

        $this->agir($atendente, $chamado, 'fechar')->assertForbidden();
        $this->agir($atendente, $chamado, 'reabrir')->assertForbidden();
    }

    public function test_cancelar_so_antes_de_alguem_estar_trabalhando(): void
    {
        $aberto = Chamado::factory()->create();
        $this->agir($aberto->solicitante, $aberto, 'cancelar')->assertOk()->assertJsonPath('data.estado', 'cancelado');

        $emAtendimento = $this->emAtendimentoPor(User::factory()->atendente()->create());
        $this->agir($emAtendimento->solicitante, $emAtendimento, 'cancelar')->assertUnprocessable();
    }

    public function test_quem_nao_ve_o_chamado_recebe_404_antes_de_qualquer_regra(): void
    {
        $chamado = Chamado::factory()->create();
        $outro = User::factory()->create();

        // Sem o 404 primeiro, o 403 ou o 422 confirmariam que o chamado existe.
        $this->agir($outro, $chamado, 'cancelar')->assertNotFound();
        $this->agir($outro, $chamado, 'resolver')->assertNotFound();
        $this->assertSame(EstadoChamado::Aberto, $chamado->fresh()->estado);
    }

    public function test_acao_que_nao_existe_responde_404(): void
    {
        $chamado = Chamado::factory()->create();

        $this->agir(User::factory()->admin()->create(), $chamado, 'apagar')->assertNotFound();
    }

    public function test_cada_um_ve_as_proprias_acoes_disponiveis(): void
    {
        $chamado = Chamado::factory()->create();

        Sanctum::actingAs($chamado->solicitante);
        $this->getJson("/api/chamados/{$chamado->id}")->assertJsonPath('data.acoes_disponiveis', ['cancelar']);

        Sanctum::actingAs(User::factory()->atendente()->create());
        $this->getJson("/api/chamados/{$chamado->id}")->assertJsonPath('data.acoes_disponiveis', ['assumir']);

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->getJson("/api/chamados/{$chamado->id}")->assertJsonPath('data.acoes_disponiveis', ['assumir', 'cancelar']);
    }
}
