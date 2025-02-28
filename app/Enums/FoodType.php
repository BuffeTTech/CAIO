<?php

namespace App\Enums;

enum FoodType: string {

    use EnumToArray;
    
    case ITEM_FIXO = 'ITEM_FIXO';
    case ITEM_INSUMO = 'ITEM_INSUMO';
}