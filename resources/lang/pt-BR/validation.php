<?php
return [
    'required' => 'O campo :attribute é obrigatório.',
    'email' => 'O campo :attribute deve ser um endereço de email válido.',
    'number' => 'O campo :attribute deve ser um número.',
    'string' => 'O campo :attribute deve ser uma string.',
    'regex' => 'O campo :attribute não está no formato correto.',
    'uuid' => 'O campo :attribute deve ser um UUID válido.',
    'size' => 'O campo :attribute deve ter exatamente :size caracteres.',
    'max' => [
        'string' => 'O campo :attribute não pode ter mais que :max caracteres.',
    ],
    'custom' => [
        'address.zipcode' => [
            'required' => 'O campo CEP é obrigatório.',
            'regex' => 'O CEP deve estar no formato 00000-000.',
        ],
        'details.phone' => [
            'regex' => 'O telefone deve estar no formato (xx) xxxxx-xxxx.',
        ],
    ],
    'attributes' => [
        'address.zipcode' => 'CEP',
        'address.street' => 'rua',
        'address.string' => 'número',
        'address.neighborhood' => 'bairro',
        'address.state' => 'estado',
        'address.city' => 'cidade',
        'address.complement' => 'complemento',
        'details.name' => 'nome',
        'details.email' => 'email',
        'details.phone' => 'telefone',
        'user_id' => 'ID do usuário',
    ],
];