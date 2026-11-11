<?php

namespace App\Services;

use App\Enums\Acao;
use App\Exceptions\ChamadoMudouNoMeioDoCaminho;
use App\Exceptions\TransicaoInvalida;
use App\Models\Chamado;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Único caminho para mudar o estado de um chamado.
 */
class MaquinaDeEstados
{
    /**
     * O que o usuário pode fazer com o chamado agora: vale no estado atual e
     * a policy deixa.
     *
     * @return list<Acao>
     */
    public function acoesDisponiveis(Chamado $chamado, User $usuario): array
    {
        return array_values(array_filter(
            Acao::cases(),
            fn (Acao $acao) => $acao->parteDe($chamado->estado)
                && Gate::forUser($usuario)->allows('executar', [$chamado, $acao]),
        ));
    }

    /**
     * A ordem das checagens é parte do contrato: primeiro o estado (422, com
     * o que dá pra fazer), depois a permissão (403), por fim a gravação, que
     * só acontece se ninguém mexeu no chamado desde a leitura (409).
     */
    public function executar(Chamado $chamado, Acao $acao, User $ator): Chamado
    {
        if (! $acao->parteDe($chamado->estado)) {
            throw new TransicaoInvalida($acao, $chamado->estado, $this->acoesDisponiveis($chamado, $ator));
        }

        Gate::forUser($ator)->authorize('executar', [$chamado, $acao]);

        $mudancas = ['estado' => $acao->destino(), 'versao' => $chamado->versao + 1];

        if ($acao === Acao::Assumir) {
            $mudancas['atendente_id'] = $ator->id;
        } elseif ($acao === Acao::DevolverAFila) {
            $mudancas['atendente_id'] = null;
        }

        // Compara a versão, e não o estado: o chamado pode sair do estado
        // lido e voltar a ele, com outro atendente, enquanto esta requisição
        // decidia. O estado bateria; a versão, não.
        $gravou = Chamado::query()
            ->whereKey($chamado->getKey())
            ->where('versao', $chamado->versao)
            ->update($mudancas);

        if ($gravou === 0) {
            throw new ChamadoMudouNoMeioDoCaminho;
        }

        return $chamado->refresh();
    }
}
