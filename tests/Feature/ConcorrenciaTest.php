<?php

namespace Tests\Feature;

use App\Enums\Acao;
use App\Enums\EstadoChamado;
use App\Exceptions\ChamadoMudouNoMeioDoCaminho;
use App\Models\Chamado;
use App\Models\User;
use App\Services\MaquinaDeEstados;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConcorrenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_dois_atendentes_ao_mesmo_tempo_so_um_fica_com_o_chamado(): void
    {
        $chamado = Chamado::factory()->create();
        $ana = User::factory()->atendente()->create();
        $bia = User::factory()->atendente()->create();

        // Simula a corrida: no instante em que a requisição da Ana carrega o
        // chamado, a Bia assume e grava primeiro. A Ana decide com o chamado
        // ainda "aberto" na mão, e só a gravação pode perceber.
        $interferiu = false;
        Chamado::retrieved(function (Chamado $lido) use (&$interferiu, $bia) {
            if ($interferiu) {
                return;
            }
            $interferiu = true;
            app(MaquinaDeEstados::class)->executar(Chamado::find($lido->id), Acao::Assumir, $bia);
        });

        Sanctum::actingAs($ana, ['*']);
        $this->postJson("/api/chamados/{$chamado->id}/acoes/assumir")->assertConflict();

        $this->assertTrue($interferiu);
        $this->assertSame($bia->id, $chamado->fresh()->atendente_id);
        $this->assertSame(1, $chamado->fresh()->versao);
    }

    public function test_estado_igual_ao_lido_nao_basta_se_o_chamado_mudou_no_meio(): void
    {
        $ana = User::factory()->atendente()->create();
        $bia = User::factory()->atendente()->create();
        $maquina = app(MaquinaDeEstados::class);
        $chamado = Chamado::factory()->create();
        $maquina->executar($chamado, Acao::Assumir, $ana);

        // A Ana lê o chamado em atendimento com ela...
        $lidoPelaAna = Chamado::find($chamado->id);

        // ...enquanto isso ele é devolvido e a Bia assume: o estado volta a ser
        // "em atendimento", mas o chamado agora é dela.
        $maquina->executar(Chamado::find($chamado->id), Acao::DevolverAFila, $ana, 'Vou sair de férias.');
        $maquina->executar(Chamado::find($chamado->id), Acao::Assumir, $bia);

        // Comparar só o estado deixaria a Ana resolver o chamado da Bia.
        $this->assertThrows(fn () => $maquina->executar($lidoPelaAna, Acao::Resolver, $ana, 'Resolvido.'), ChamadoMudouNoMeioDoCaminho::class);

        $this->assertSame(EstadoChamado::EmAtendimento, $chamado->fresh()->estado);
        $this->assertSame($bia->id, $chamado->fresh()->atendente_id);
    }
}
