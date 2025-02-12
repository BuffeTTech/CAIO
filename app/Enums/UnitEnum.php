<?php

namespace App\Enums;

enum UnitEnum: string {

    use EnumToArray;
    
    case UNID = "unid";
    case KG = "kg";
}