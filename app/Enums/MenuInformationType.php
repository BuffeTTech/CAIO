<?php

namespace App\Enums;

enum MenuInformationType: string {

    use EnumToArray;

    case SERVICES = "Serviços";
    case EVENT = "Evento";
    case EMPLOYEES = "Funcionários";
}