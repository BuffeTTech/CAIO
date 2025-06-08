<?php

namespace App\Enums;

enum IngredientSourceType: string {

    use EnumToArray;

    case SUPPLIER = "Fornecedor";
    case MARKET = "Atacado";
}