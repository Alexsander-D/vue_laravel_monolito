<?php

namespace App\Enums;

enum QueueStatus: string
{
    case DESCARTE = 'DESCARTE';
    case RECUPERADO = 'RECUPERADO';
    case ANALISE = 'ANALISE';
    case PENDENTE = 'PENDENTE';
}
