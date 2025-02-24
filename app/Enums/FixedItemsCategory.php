<?php

namespace App\Enums;

enum FixedItemsCategory: string {

    use EnumToArray;

    case LIMPEZA = "LIMPEZA";
    case DESCARTAVEL = "DESCARTAVEL";
    case TEMPERO = "TEMPERO";
    case UTENSILIO = "UTENSILIO";
    case BEBIDA = "BEBIDA";
}