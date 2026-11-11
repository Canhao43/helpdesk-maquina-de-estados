<?php

namespace App\Policies;

use App\Enums\Acao;
use App\Enums\Papel;
use App\Models\Chamado;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ChamadoPolicy
{
    /**
     * Chamado de outra pessoa responde 404, igual a um que não existe. Com
     * 403, quem testasse ids em sequência descobriria quais existem.
     */
    public function view(User $usuario, Chamado $chamado): Response
    {
        return $usuario->papel->atende() || $chamado->solicitante_id === $usuario->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Qualquer usuário abre chamado, inclusive atendente: quem atende também
     * tem problema para relatar.
     */
    public function create(User $usuario): bool
    {
        return true;
    }

    /**
     * Quem pode executar cada ação. Se a ação vale no estado atual é outra
     * pergunta, respondida pela tabela em Acao.
     */
    public function executar(User $usuario, Chamado $chamado, Acao $acao): Response
    {
        $dono = $chamado->solicitante_id === $usuario->id;
        $responsavel = $chamado->atendente_id === $usuario->id;
        $admin = $usuario->papel === Papel::Admin;

        $pode = match ($acao) {
            Acao::Assumir => $usuario->papel->atende(),
            // Só quem está com o chamado mexe nele; admin cobre ausência.
            Acao::DevolverAFila, Acao::PedirInformacao, Acao::Resolver => $responsavel || $admin,
            // Quem confirma que o problema acabou é quem tinha o problema.
            Acao::Responder, Acao::Reabrir, Acao::Fechar => $dono,
            Acao::Cancelar => $dono || $admin,
        };

        return $pode ? Response::allow() : Response::deny('Esta ação não é sua neste chamado.');
    }
}
