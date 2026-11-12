<?php

namespace App\Sla;

use App\Enums\Acao;
use App\Enums\EstadoChamado;
use App\Enums\Prioridade;
use Carbon\CarbonImmutable;

/**
 * O SLA não fica guardado: é recalculado da linha do tempo do histórico.
 * Assim não existe contador para dessincronizar, e mudar a regra (um estado
 * a mais pausando o relógio, por exemplo) vale também para o passado.
 *
 * Sem banco e sem relógio do sistema: recebe os marcos e o "agora", e o
 * teste fixa os dois.
 */
final class CalculadoraDeSla
{
    /**
     * A bola está com quem atende. Aguardando o solicitante e resolvido
     * pausam; fechado e cancelado param.
     */
    private const RELOGIO_CORRE_EM = [EstadoChamado::Aberto, EstadoChamado::EmAtendimento];

    /**
     * @param  list<Marco>  $marcos  em ordem; o primeiro é a abertura
     */
    public function calcular(Prioridade $prioridade, array $marcos, CarbonImmutable $agora): ?Sla
    {
        if ($marcos === []) {
            return null;
        }

        $estadoAtual = $marcos[array_key_last($marcos)]->estado;

        $consumido = 0;
        foreach ($marcos as $i => $marco) {
            if (in_array($marco->estado, self::RELOGIO_CORRE_EM, true)) {
                $fim = isset($marcos[$i + 1]) ? $marcos[$i + 1]->momento : $agora;
                $consumido += max(0, (int) $marco->momento->diffInSeconds($fim));
            }
        }

        $limite = $prioridade->minutosParaResolucao() * 60;
        $relogio = match (true) {
            in_array($estadoAtual, self::RELOGIO_CORRE_EM, true) => 'correndo',
            $estadoAtual->final() => 'parado',
            default => 'pausado',
        };

        return new Sla(
            primeiraResposta: $this->primeiraResposta($prioridade, $marcos, $estadoAtual, $agora),
            limiteDaResolucaoEmMinutos: $prioridade->minutosParaResolucao(),
            consumidoEmMinutos: intdiv($consumido, 60),
            relogio: $relogio,
            // Só dá para prever o vencimento com o relógio correndo. Pausado,
            // depende de quando o solicitante voltar.
            resolucaoVenceEm: $relogio === 'correndo' ? $agora->addSeconds($limite - $consumido) : null,
            resolucaoEstourada: $consumido > $limite,
        );
    }

    /**
     * @param  list<Marco>  $marcos
     */
    private function primeiraResposta(Prioridade $prioridade, array $marcos, EstadoChamado $estadoAtual, CarbonImmutable $agora): ?PrimeiraResposta
    {
        $respondidaEm = null;
        foreach ($marcos as $marco) {
            if ($marco->acao === Acao::Assumir) {
                $respondidaEm = $marco->momento;
                break;
            }
        }

        // Cancelado antes de alguém assumir: não havia a quem responder.
        if ($respondidaEm === null && $estadoAtual->final()) {
            return null;
        }

        $prazo = $marcos[0]->momento->addMinutes($prioridade->minutosParaPrimeiraResposta());

        return new PrimeiraResposta(
            limiteEmMinutos: $prioridade->minutosParaPrimeiraResposta(),
            prazo: $prazo,
            respondidaEm: $respondidaEm,
            estourado: ($respondidaEm ?? $agora)->greaterThan($prazo),
        );
    }
}
