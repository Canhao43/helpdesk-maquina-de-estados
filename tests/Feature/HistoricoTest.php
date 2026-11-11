<?php

namespace Tests\Feature;

use App\Enums\Acao;
use App\Enums\EstadoChamado;
use App\Models\Chamado;
use App\Models\EventoDoChamado;
use App\Models\User;
use App\Services\MaquinaDeEstados;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class HistoricoTest extends TestCase
{
    use RefreshDatabase;

    private function agir(User $quem, Chamado $chamado, string $acao, ?string $comentario = null): TestResponse
    {
        Sanctum::actingAs($quem, ['*']);

        return $this->postJson("/api/chamados/{$chamado->id}/acoes/{$acao}", array_filter(['comentario' => $comentario]));
    }

    private function abrirPelaApi(User $solicitante): Chamado
    {
        Sanctum::actingAs($solicitante, ['*']);
        $id = $this->postJson('/api/chamados', ['titulo' => 'Sem rede', 'descricao' => 'O cabo está ligado.'])->json('data.id');

        return Chamado::findOrFail($id);
    }

    public function test_cada_passo_do_chamado_fica_no_historico_em_ordem(): void
    {
        $solicitante = User::factory()->create();
        $atendente = User::factory()->atendente()->create();
        $chamado = $this->abrirPelaApi($solicitante);

        $this->agir($atendente, $chamado, 'assumir')->assertOk();
        $this->agir($atendente, $chamado, 'pedir_informacao', 'Qual a sala?')->assertOk();
        $this->agir($solicitante, $chamado, 'responder', 'Sala 12.')->assertOk();
        $this->agir($atendente, $chamado, 'resolver', 'Porta do switch trocada.')->assertOk();

        Sanctum::actingAs($solicitante, ['*']);
        $historico = $this->getJson("/api/chamados/{$chamado->id}/historico")->assertOk()->json('data');

        $this->assertSame(
            [
                ['abrir', null, 'aberto', null, $solicitante->id],
                ['assumir', 'aberto', 'em_atendimento', null, $atendente->id],
                ['pedir_informacao', 'em_atendimento', 'aguardando_solicitante', 'Qual a sala?', $atendente->id],
                ['responder', 'aguardando_solicitante', 'em_atendimento', 'Sala 12.', $solicitante->id],
                ['resolver', 'em_atendimento', 'resolvido', 'Porta do switch trocada.', $atendente->id],
            ],
            array_map(fn (array $e) => [$e['acao'], $e['estado_anterior'], $e['estado_novo'], $e['comentario'], $e['autor']['id']], $historico),
        );
    }

    public function test_acao_que_pede_comentario_nao_passa_sem_ele_e_nao_muda_nada(): void
    {
        $atendente = User::factory()->atendente()->create();
        $chamado = Chamado::factory()->create(['estado' => EstadoChamado::EmAtendimento, 'atendente_id' => $atendente->id]);

        $this->agir($atendente, $chamado, 'resolver')->assertUnprocessable()->assertJsonValidationErrors('comentario');
        $this->agir($atendente, $chamado, 'resolver', '   ')->assertUnprocessable()->assertJsonValidationErrors('comentario');

        $this->assertSame(EstadoChamado::EmAtendimento, $chamado->fresh()->estado);
        $this->assertSame(0, $chamado->fresh()->versao);
        $this->assertSame(0, EventoDoChamado::count());
    }

    public function test_sem_permissao_responde_403_antes_de_reclamar_do_comentario(): void
    {
        $chamado = Chamado::factory()->create(['estado' => EstadoChamado::EmAtendimento, 'atendente_id' => User::factory()->atendente()]);

        // O solicitante não resolve; pedir comentário a ele seria pedir algo inútil.
        $this->agir($chamado->solicitante, $chamado, 'resolver')->assertForbidden();
    }

    public function test_quem_nao_ve_o_chamado_nao_ve_o_historico(): void
    {
        $chamado = Chamado::factory()->create();
        Sanctum::actingAs(User::factory()->create(), ['*']);

        $this->getJson("/api/chamados/{$chamado->id}/historico")->assertNotFound();
    }

    public function test_se_o_evento_nao_grava_a_transicao_tambem_nao_vale(): void
    {
        $atendente = User::factory()->atendente()->create();
        $chamado = Chamado::factory()->create();
        EventoDoChamado::creating(fn () => throw new RuntimeException('disco cheio'));

        $this->withoutExceptionHandling();
        $this->assertThrows(fn () => $this->agir($atendente, $chamado, 'assumir'), RuntimeException::class);

        $this->assertSame(EstadoChamado::Aberto, $chamado->fresh()->estado);
        $this->assertNull($chamado->fresh()->atendente_id);
        $this->assertSame(0, $chamado->fresh()->versao);
    }

    public function test_quem_perde_a_corrida_nao_deixa_rastro_no_historico(): void
    {
        $chamado = Chamado::factory()->create();
        $lido = Chamado::find($chamado->id);
        $maquina = app(MaquinaDeEstados::class);

        $maquina->executar(Chamado::find($chamado->id), Acao::Assumir, User::factory()->atendente()->create());
        $this->assertThrows(fn () => $maquina->executar($lido, Acao::Assumir, User::factory()->atendente()->create()));

        $this->assertSame(1, EventoDoChamado::count());
    }

    public function test_o_model_recusa_alterar_e_apagar_evento(): void
    {
        $chamado = $this->abrirPelaApi(User::factory()->create());
        $evento = $chamado->eventos()->first();

        $this->assertThrows(fn () => $evento->update(['comentario' => 'reescrito']), LogicException::class);
        $this->assertThrows(fn () => $evento->delete(), LogicException::class);
    }

    public function test_o_banco_recusa_alterar_e_apagar_evento_mesmo_por_fora_do_model(): void
    {
        $chamado = $this->abrirPelaApi(User::factory()->create());

        $this->assertThrows(fn () => DB::table('eventos_do_chamado')->update(['comentario' => 'reescrito']), QueryException::class, 'não pode ser alterado');
        $this->assertThrows(fn () => DB::table('eventos_do_chamado')->delete(), QueryException::class, 'não pode ser apagado');

        $this->assertSame(1, $chamado->eventos()->count());
    }
}
