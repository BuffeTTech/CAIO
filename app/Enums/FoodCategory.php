<?php

namespace App\Enums;

enum FoodCategory: string {

    use EnumToArray;
    
    case ENTRADAS = "ENTRADAS";
    case GUARNICOES = "GUARNIÇÕES";
    case ASSADOS = "ASSADOS";
    case SALADAS = "SALADAS";
    case BEBIDAS = "BEBIDAS";
    case SOBREMESA = "SOBREMESA";
    case FRUTAS = "FRUTAS";
    case DOCES = "DOCES";
    case FRIOS_OUTROS = "FRIOS E OUTROS";
    case CANAPES = "CANAPÉS";
    case PALITINHOS = "PALITINHOS";
    case LANCHES_FRIOS = "LANCHES FRIOS";
    case LIMPEZA = "LIMPEZA";
    case DESCARTAVEL = "DESCARTAVEL";
    case TEMPERO = "TEMPERO";
    case UTENSILIO = "UTENSILIO";
    case BEBIDA = "BEBIDA";

    public static function foodItems(): array {
        return [
            self::ENTRADAS,
            self::GUARNICOES,
            self::ASSADOS,
            self::SALADAS,
            self::BEBIDAS,
            self::SOBREMESA,
            self::FRUTAS,
            self::DOCES,
            self::FRIOS_OUTROS,
            self::CANAPES,
            self::PALITINHOS,
            self::LANCHES_FRIOS,
        ];
    }

    public static function nonFoodItems(): array {
        return [
            self::LIMPEZA,
            self::DESCARTAVEL,
            self::TEMPERO,
            self::UTENSILIO,
            self::BEBIDA,
        ];
    }
}