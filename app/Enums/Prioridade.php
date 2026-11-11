<?php

namespace App\Enums;

enum Prioridade: string
{
    case Baixa = 'baixa';
    case Media = 'media';
    case Alta = 'alta';
    case Critica = 'critica';
}
