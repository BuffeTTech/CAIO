<?php

namespace Database\Seeders;

use app\Models\FixedItems;
use App\Enums\FixedItemsCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use PhpOffice\PhpSpreadsheet\IOFactory;
require 'vendor/autoload.php';

class FixedItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $inputFileName = 'database/seeders/CHEKLIST 2024.xlsx';

        // Carrega a planilha
        $spreadsheet = IOFactory::load($inputFileName);

        // Seleciona a aba ativa (ou use ->getSheetByName('nome_da_aba') se precisar)
        $worksheet = $spreadsheet->getActiveSheet();

        // Variável para guardar a categoria atual
        $currentCategory = null;

        // Percorre a partir da linha 70 em diante
        foreach ($worksheet->getRowIterator(70) as $row) {
            // Lê as células da linha
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            // Monta um array com os valores das células
            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }

            // Supondo que:
            // - COLUNA A (índice 0) = categoria (quando preenchida)
            // - COLUNA B (índice 1) = custo (ou nome do item, depende do layout real)
            // - COLUNA C (índice 2) = nome do item (ou custo)
            // Ajuste conforme a estrutura da sua planilha

            $colA = $rowData[0] ?? null; // Categoria ou vazio
            $colB = $rowData[1] ?? null; // Pode ser custo ou nome
            $colC = $rowData[2] ?? null; // Pode ser nome ou custo

            // Se COLUNA A não estiver vazia, significa que esta linha define uma nova categoria
            if (!empty($colA)) {
                $currentCategory = $colA;
                if($currentCategory == 'CAIXA DE LIMPEZA'){
                    $currentCategory = FixedItemsCategory::getEnumByValue("LIMPEZA");
                }
                if($currentCategory == 'CAIXA DESCARTAVEL'){
                    $currentCategory = FixedItemsCategory::getEnumByValue("DESCARTAVEL");
                }
                if($currentCategory == 'CAIXA DE TEMPERO'){
                    $currentCategory = FixedItemsCategory::getEnumByValue("TEMPERO");
                }
                continue;
            }

            // Se chegou aqui, COLUNA A está vazia, então é um item pertencente à categoria atual
            // Ajuste conforme sua planilha. Por exemplo:
            $name = $colB;     // nome do item
            $qtd = $colC;     // custo do item
            $unit = '';        // se precisar capturar unidade de alguma coluna
            $consumed = 0;     // se não tiver na planilha, pode definir zero ou outra lógica

            // Se não tiver nada em $name, significa linha em branco ou sem dados
            if (empty($name)) {
                continue;
            }
            
            $fixedItem = FixedItems::create([
                'name' => $name,
                'qtd' =>$qtd,
                'category'=>$currentCategory
            ]);

            // Agora insira no banco de dados (exemplo genérico)
            // Se estiver usando PDO, por exemplo:
            /*
            $stmt = $pdo->prepare("INSERT INTO sua_tabela (name, qtd, category, consumed_per_client, unit) 
                                VALUES (:name, :qtd, :category, :consumed, :unit)");
            $stmt->execute([
                ':name' => $name,
                ':qtd' => $qtd,
                ':category' => $currentCategory,
                ':consumed' => $consumed,
                ':unit' => $unit
            ]);
            */

            // Ou se estiver num framework, use o método de inserção que preferir
        }

        echo "Importação concluída!\n";

    }
}
