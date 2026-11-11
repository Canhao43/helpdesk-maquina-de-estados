<?php

namespace App\Enums;

/**
 * O que um token pode fazer. O escopo é um teto, não uma permissão: um token
 * com "chamados:agir" de um solicitante continua sem poder assumir, porque
 * quem decide isso é o papel do dono, na policy.
 */
enum Escopo: string
{
    case Ler = 'chamados:ler';
    case Abrir = 'chamados:abrir';
    case Agir = 'chamados:agir';
}
