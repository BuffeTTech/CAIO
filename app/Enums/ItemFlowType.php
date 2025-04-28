<?php

namespace App\Enums;

enum ItemFlowType: string {

    use EnumToArray;

    case  INSERTED = "Inserido";
    case  NEUTRAL = "Neutro";
    case  REMOVED = "Removido";
    case  MODIFIED = "Modificado";


}