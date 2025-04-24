<?php

namespace App\Enums;

enum DocumentType: string {

    use EnumToArray;

    case CPF = "CPF";
    case CNPJ = "CNPJ";
}