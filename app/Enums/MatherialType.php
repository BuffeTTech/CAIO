<?php

namespace App\Enums;

enum MatherialType: string {

    use EnumToArray;
    
    case EQUIPMENT = "Equipamento";
    case TOOL = "Utensilio de cozinha";
}