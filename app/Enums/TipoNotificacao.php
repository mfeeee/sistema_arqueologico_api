<?php

namespace App\Enums;

enum TipoNotificacao: string
{
    case Coleta = 'coleta';
    case Sync = 'sync';
    case Sistema = 'sistema';
}
