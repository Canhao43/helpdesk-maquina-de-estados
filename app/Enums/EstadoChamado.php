<?php

namespace App\Enums;

/**
 * Os demais estados e as transições entre eles entram com a máquina de
 * estados, no M2. Por enquanto todo chamado nasce e fica aberto.
 */
enum EstadoChamado: string
{
    case Aberto = 'aberto';
}
