<?php

namespace Database\Seeders;

use App\Imports\OrcamentoImport;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            // Tenta carregar o arquivo e verificar a estrutura
            $filePath = database_path('seeders/calculadora-orcamento.xlsx');

            // Tenta carregar o arquivo e verificar a estrutura
            Excel::import(new OrcamentoImport, $filePath);

            // Mensagem de sucesso se a importação for bem-sucedida
            echo 'Importado com sucesso!';
        } catch (ValidationException $e) {
            // Captura exceções de validação
            
        }
    }
}
