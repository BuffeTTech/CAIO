<?php

namespace App\Enums;

enum IngredientCategory: string {

    use EnumToArray;

    case SEASONINGS = "Temperos";
    case MEATS = "Carnes";
    case GRAINS = "Grãos";
    case VEGETABLES = "Vegetais";
    case FRUITS = "Frutas";
    case OILS = "Óleos";

    public static function foodIngredients(): array {
        return [
            self::SEASONINGS,
            self::MEATS,
            self::GRAINS,
            self::VEGETABLES,
            self::FRUITS,
            self::OILS,
        ];
    }
}