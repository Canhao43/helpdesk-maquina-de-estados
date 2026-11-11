<?php

namespace Tests\Unit;

use App\Enums\Acao;
use App\Enums\EstadoChamado;
use PHPUnit\Framework\TestCase;

/**
 * Confere a tabela de transições como um todo, para que uma ação nova ou um
 * estado novo não crie um beco sem saída nem uma saída de estado final.
 */
class TabelaDeTransicoesTest extends TestCase
{
    public function test_estado_final_nao_tem_saida(): void
    {
        foreach (Acao::cases() as $acao) {
            foreach ($acao->origens() as $origem) {
                $this->assertFalse($origem->final(), "{$acao->value} parte do estado final {$origem->value}");
            }
        }
    }

    public function test_todo_estado_que_nao_e_final_tem_saida(): void
    {
        foreach (EstadoChamado::cases() as $estado) {
            if ($estado->final()) {
                continue;
            }

            $saidas = array_filter(Acao::cases(), fn (Acao $acao) => $acao->parteDe($estado));
            $this->assertNotEmpty($saidas, "{$estado->value} é beco sem saída");
        }
    }

    public function test_todo_estado_e_alcancavel_a_partir_de_aberto(): void
    {
        $alcancados = [EstadoChamado::Aberto];
        $fila = [EstadoChamado::Aberto];

        while ($estado = array_shift($fila)) {
            foreach (Acao::cases() as $acao) {
                if ($acao->parteDe($estado) && ! in_array($acao->destino(), $alcancados, true)) {
                    $alcancados[] = $acao->destino();
                    $fila[] = $acao->destino();
                }
            }
        }

        $this->assertEqualsCanonicalizing(EstadoChamado::cases(), $alcancados);
    }

    public function test_nenhuma_acao_leva_ao_mesmo_estado_de_onde_parte(): void
    {
        foreach (Acao::cases() as $acao) {
            $this->assertNotContains($acao->destino(), $acao->origens(), "{$acao->value} não muda nada");
        }
    }
}
