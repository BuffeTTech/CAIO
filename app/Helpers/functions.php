<?php

if (!function_exists('format_slug')) {
    function format_slug(string $slug): string
    {
        $slug = strtolower($slug);
        return str_replace(' ', '-', $slug);;
    }
}

if (!function_exists('sanitize_string')) {
    function sanitize_string(string $string): string
    {
        // Converte para minúsculas
        $string = strtolower($string);

        // Substitui espaços por hífens
        $string = str_replace(' ', '-', $string);

        // Mapeia caracteres especiais para suas versões tradicionais
        $caracteresEspeciais = [
            // A
            'á' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'à' => 'a',
            'Á' => 'a',
            'Ã' => 'a',
            'Â' => 'a',
            'À' => 'a',
            // E
            'É' => 'e',
            'Ê' => 'e',
            'È' => 'e',
            'è' => 'e',
            'é' => 'e',
            'ê' => 'e',
            // I
            'í' => 'i',
            'ì' => 'i',
            'î' => 'i',
            'Í' => 'i',
            'Î' => 'i',
            'Ì' => 'i',
            // O
            'ó' => 'o',
            'ô' => 'o',
            'õ' => 'o',
            'ò' => 'o',
            'Ó' => 'o',
            'Ô' => 'o',
            'Õ' => 'o',
            'Ò' => 'o',
            // U
            'ú' => 'u',
            'ü' => 'u',
            'ù' => 'u',
            'û' => 'u',
            'Ú' => 'u',
            'Ù' => 'u',
            'Û' => 'u',
            'Ü' => 'u',

            // Caracteres especiais
            'ç' => 'c',
            "'" => '-',
            '"' => '-',
            '!' => '-',
            '@' => '-',
            '#' => '-',
            '$' => '-',
            '%' => '-',
            '¨' => '-',
            '*' => '-',
            ';' => '-',
        ];

        $string = strtr($string, $caracteresEspeciais);

        return $string;
    }
}