<?php

namespace App\Policies;

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
}
