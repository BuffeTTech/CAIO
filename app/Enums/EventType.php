<?php

namespace App\Enums;

enum EventType: string {

    use EnumToArray;

    case CLOSED_ESTIMATE = "ORÇAMENTO FECHADO";
    case OPEN_ESTIMATE = "ORÇAMENTO EM ABERTO";
    case CLOSED_EVENT = "EVENTO ENCERRADO";
    // case CANCEL_ESTIMATE = "ORÇAMENTO CANCELADO";
}