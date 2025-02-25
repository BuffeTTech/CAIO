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
    case ITEM_FIXO = 'ITEM_FIXO';
    case ITEM_INSUMO = 'ITEM_INSUMO';
}