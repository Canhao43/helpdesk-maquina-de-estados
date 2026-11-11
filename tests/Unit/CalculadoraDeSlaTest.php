<?php

namespace Tests\Unit;

use App\Enums\Acao;
use App\Enums\EstadoChamado as E;
use App\Enums\Prioridade;
use App\Sla\CalculadoraDeSla;
use App\Sla\Marco;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class CalculadoraDeSlaTest extends TestCase
{
    private CarbonImmutable $abertura;

    protected function setUp(): void
    {
        parent::setUp();
        $this->abertura = CarbonImmutable::parse('2026-11-10 09:00:00');
    }

    /**
     * Marco a N minutos da abertura.
     */
    private function aos(int $minutos, E $estado, ?Acao $acao = null): Marco
    {
        return new Marco($this->abertura->addMinutes($minutos), $estado, $acao);
    }

    private function agora(int $minutos): CarbonImmutable
    {
        return $this->abertura->addMinutes($minutos);
    }

    private function calcular(Prioridade $prioridade, array $marcos, int $agora)
    {
        return (new CalculadoraDeSla)->calcular($prioridade, $marcos, $this->agora($agora));
    }

    public function test_chamado_aberto_ha_uma_hora(): void
    {
        $sla = $this->calcular(Prioridade::Media, [$this->aos(0, E::Aberto)], 60);

        $this->assertSame(60, $sla->consumidoEmMinutos);
        $this->assertSame('correndo', $sla->relogio);
        $this->assertEquals($this->agora(24 * 60), $sla->resolucaoVenceEm);
        $this->assertFalse($sla->resolucaoEstourada);
        $this->assertEquals($this->agora(8 * 60), $sla->primeiraResposta->prazo);
        $this->assertNull($sla->primeiraResposta->respondidaEm);
        $this->assertFalse($sla->primeiraResposta->estourado);
    }

    public function test_aguardando_o_solicitante_o_relogio_para(): void
    {
        $marcos = [
            $this->aos(0, E::Aberto),
            $this->aos(60, E::EmAtendimento, Acao::Assumir),
            $this->aos(120, E::AguardandoSolicitante, Acao::PedirInformacao),
        ];

        // Cinco horas esperando o solicitante não contam.
        $sla = $this->calcular(Prioridade::Media, $marcos, 120 + 5 * 60);

        $this->assertSame(120, $sla->consumidoEmMinutos);
        $this->assertSame('pausado', $sla->relogio);
        $this->assertNull($sla->resolucaoVenceEm);
    }

    public function test_depois_da_resposta_o_relogio_volta_de_onde_parou(): void
    {
        $marcos = [
            $this->aos(0, E::Aberto),
            $this->aos(60, E::EmAtendimento, Acao::Assumir),
            $this->aos(120, E::AguardandoSolicitante, Acao::PedirInformacao),
            $this->aos(420, E::EmAtendimento, Acao::Responder),
        ];

        $sla = $this->calcular(Prioridade::Media, $marcos, 450);

        $this->assertSame(150, $sla->consumidoEmMinutos);
        $this->assertSame('correndo', $sla->relogio);
        $this->assertEquals($this->agora(450 + 24 * 60 - 150), $sla->resolucaoVenceEm);
    }

    public function test_resolvido_pausa_e_reaberto_continua_sem_contar_o_tempo_resolvido(): void
    {
        $marcos = [
            $this->aos(0, E::Aberto),
            $this->aos(10, E::EmAtendimento, Acao::Assumir),
            $this->aos(100, E::Resolvido, Acao::Resolver),
            $this->aos(1000, E::EmAtendimento, Acao::Reabrir),
        ];

        $sla = $this->calcular(Prioridade::Media, $marcos, 1030);

        $this->assertSame(130, $sla->consumidoEmMinutos);
        $this->assertSame('correndo', $sla->relogio);
    }

    public function test_critica_sem_ninguem_assumir_estoura_os_dois_prazos(): void
    {
        $sla = $this->calcular(Prioridade::Critica, [$this->aos(0, E::Aberto)], 5 * 60);

        $this->assertTrue($sla->primeiraResposta->estourado);
        $this->assertTrue($sla->resolucaoEstourada);
        // Vencido há uma hora: o vencimento fica no passado, não some.
        $this->assertEquals($this->agora(4 * 60), $sla->resolucaoVenceEm);
    }

    public function test_primeira_resposta_dentro_do_prazo_fica_cumprida_para_sempre(): void
    {
        $marcos = [
            $this->aos(0, E::Aberto),
            $this->aos(20, E::EmAtendimento, Acao::Assumir),
            $this->aos(40, E::Aberto, Acao::DevolverAFila),
            // Assumido de novo muito depois do prazo: não pode virar a "primeira".
            $this->aos(500, E::EmAtendimento, Acao::Assumir),
        ];

        // Devolvido à fila: a primeira resposta já aconteceu e não recomeça.
        $sla = $this->calcular(Prioridade::Critica, $marcos, 10 * 60);

        $this->assertEquals($this->agora(20), $sla->primeiraResposta->respondidaEm);
        $this->assertFalse($sla->primeiraResposta->estourado);
    }

    public function test_fechado_congela_o_relogio(): void
    {
        $marcos = [
            $this->aos(0, E::Aberto),
            $this->aos(30, E::EmAtendimento, Acao::Assumir),
            $this->aos(90, E::Resolvido, Acao::Resolver),
            $this->aos(200, E::Fechado, Acao::Fechar),
        ];

        $sla = $this->calcular(Prioridade::Alta, $marcos, 99_999);

        $this->assertSame(90, $sla->consumidoEmMinutos);
        $this->assertSame('parado', $sla->relogio);
        $this->assertNull($sla->resolucaoVenceEm);
        $this->assertFalse($sla->resolucaoEstourada);
    }

    public function test_cancelado_antes_de_alguem_assumir_nao_tem_primeira_resposta(): void
    {
        $sla = $this->calcular(Prioridade::Baixa, [$this->aos(0, E::Aberto), $this->aos(5, E::Cancelado, Acao::Cancelar)], 60);

        $this->assertNull($sla->primeiraResposta);
        $this->assertSame(5, $sla->consumidoEmMinutos);
    }

    public function test_sem_historico_nao_ha_sla(): void
    {
        $this->assertNull($this->calcular(Prioridade::Media, [], 0));
    }

    public function test_exatamente_no_limite_ainda_nao_estourou(): void
    {
        $sla = $this->calcular(Prioridade::Critica, [$this->aos(0, E::Aberto)], 4 * 60);

        $this->assertFalse($sla->resolucaoEstourada);
        $this->assertFalse($this->calcular(Prioridade::Critica, [$this->aos(0, E::Aberto)], 30)->primeiraResposta->estourado);
    }
}
