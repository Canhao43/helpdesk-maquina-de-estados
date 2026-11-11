<?php

namespace App\Services;

use App\Enums\Acao;
use App\Enums\EstadoChamado;
use App\Enums\Prioridade;
use App\Exceptions\ChamadoMudouNoMeioDoCaminho;
use App\Exceptions\TransicaoInvalida;
use App\Models\Chamado;
use App\Models\EventoDoChamado;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Único caminho para criar um chamado ou mudar o estado dele. Toda mudança
 * deixa um evento no histórico, na mesma transação.
 */
class MaquinaDeEstados
{
    private const LIMITE_DO_COMENTARIO = 5000;

    /**
     * @param  array{titulo: string, descricao: string, prioridade?: string|null}  $dados
     */
    public function abrir(array $dados, User $solicitante): Chamado
    {
        return DB::transaction(function () use ($dados, $solicitante) {
            $chamado = new Chamado($dados);
            $chamado->prioridade ??= Prioridade::Media;
            $chamado->estado = EstadoChamado::Aberto;
            $chamado->solicitante()->associate($solicitante);
            $chamado->save();

            $this->registrar($chamado, $solicitante, null, null, EstadoChamado::Aberto, null);

            return $chamado;
        });
    }

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
     * o que dá pra fazer), depois a permissão (403), depois o comentário
     * (422 de validação: não adianta pedir comentário de quem não pode agir),
     * por fim a gravação, que só acontece se ninguém mexeu no chamado desde
     * a leitura (409).
     */
    public function executar(Chamado $chamado, Acao $acao, User $ator, mixed $comentario = null): Chamado
    {
        if (! $acao->parteDe($chamado->estado)) {
            throw new TransicaoInvalida($acao, $chamado->estado, $this->acoesDisponiveis($chamado, $ator));
        }

        Gate::forUser($ator)->authorize('executar', [$chamado, $acao]);

        $comentario = $this->validarComentario($acao, $comentario);

        $estadoAnterior = $chamado->estado;
        $mudancas = ['estado' => $acao->destino(), 'versao' => $chamado->versao + 1];

        if ($acao === Acao::Assumir) {
            $mudancas['atendente_id'] = $ator->id;
        } elseif ($acao === Acao::DevolverAFila) {
            $mudancas['atendente_id'] = null;
        }

        DB::transaction(function () use ($chamado, $acao, $ator, $comentario, $estadoAnterior, $mudancas) {
            // Compara a versão, e não o estado: o chamado pode sair do estado
            // lido e voltar a ele, com outro atendente, enquanto esta
            // requisição decidia. O estado bateria; a versão, não.
            $gravou = Chamado::query()
                ->whereKey($chamado->getKey())
                ->where('versao', $chamado->versao)
                ->update($mudancas);

            if ($gravou === 0) {
                throw new ChamadoMudouNoMeioDoCaminho;
            }

            // Na mesma transação: se o evento não grava, a transição também
            // não vale. Não existe mudança de estado sem histórico.
            $this->registrar($chamado, $ator, $acao, $estadoAnterior, $acao->destino(), $comentario);
        });

        return $chamado->refresh();
    }

    private function validarComentario(Acao $acao, mixed $comentario): ?string
    {
        if ($comentario !== null && ! is_string($comentario)) {
            throw ValidationException::withMessages(['comentario' => 'O comentário precisa ser texto.']);
        }

        $comentario = trim((string) $comentario) ?: null;

        if ($comentario === null && $acao->exigeComentario()) {
            throw ValidationException::withMessages(['comentario' => "A ação \"{$acao->value}\" precisa de um comentário."]);
        }

        if ($comentario !== null && mb_strlen($comentario) > self::LIMITE_DO_COMENTARIO) {
            throw ValidationException::withMessages(['comentario' => 'O comentário passa de '.self::LIMITE_DO_COMENTARIO.' caracteres.']);
        }

        return $comentario;
    }

    private function registrar(Chamado $chamado, User $autor, ?Acao $acao, ?EstadoChamado $de, EstadoChamado $para, ?string $comentario): void
    {
        EventoDoChamado::create([
            'chamado_id' => $chamado->getKey(),
            'autor_id' => $autor->getKey(),
            'acao' => $acao,
            'estado_anterior' => $de,
            'estado_novo' => $para,
            'comentario' => $comentario,
        ]);
    }
}
