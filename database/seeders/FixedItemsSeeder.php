<?php

namespace Database\Seeders;

use App\Enums\FoodCategory;
use App\Models\FixedItems;
use App\Enums\FixedItemsCategory;
use App\Models\Menu\Item;
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
        $sheetsNames = [
            'COMIDA BOTECO',
            'FEIJOADA',
            'ARRAIÁ',
            'FATIADOS PADRÃO',
            'NATAL',
            'FATIADOS PREMIUM',
            'PERSONALIZADO',
            'ESPETINHOS',
            //'KIDS',
            'ESBOÇO NOVO CHECKLIST',
            'SUPER PREMIUM'
        ];

        $sheetsLines = [
            70,
            62,
            55,
            100,
            85,
            56,
            24,
            39,
            //37,
            142,
            29
        ];

        $fixedItemEnum = FoodCategory::getEnumByName("ITEM_FIXO");
        $inputFileName = 'database/seeders/CHEKLIST 2024.xlsx';

        // Carrega a planilha
        $spreadsheet = IOFactory::load($inputFileName);

        // Seleciona a aba ativa (ou use ->getSheetByName('nome_da_aba') se precisar)
        $worksheet = $spreadsheet->getActiveSheet();

        for($i = 0;$i <= 9;$i++){
            // Variável para guardar a categoria atual
            $currentCategory = null;
            $worksheet = $spreadsheet->getSheetByName($sheetsNames[$i]);
            if ($worksheet === null) {
                die("A aba" + $sheetsNames[$i] + " não foi encontrada na planilha.\n");
            }
            echo $sheetsNames[$i];
            // Percorre a partir da linha 70 em diante
            foreach ($worksheet->getRowIterator($sheetsLines[$i]) as $row) {
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
                    if($currentCategory == 'CAIXA  DE LIMPEZA'){
                        $currentCategory = FixedItemsCategory::getEnumByValue("LIMPEZA");
                    }
                    if($currentCategory == 'CAIXA DESCARTAVEL'){
                        $currentCategory = FixedItemsCategory::getEnumByValue("DESCARTAVEL");
                    }
                    if($currentCategory == 'CAIXA DE TEMPERO'){
                        $currentCategory = FixedItemsCategory::getEnumByValue("TEMPERO");
                    }
                    if($currentCategory == 'UTENSILIOS GERAL'){
                        $currentCategory = FixedItemsCategory::getEnumByValue("UTENSILIO");
                    }
                    if($currentCategory == 'BEBIDAS'){
                        $currentCategory = FixedItemsCategory::getEnumByValue("BEBIDA");
                    }
                    continue;
                }

                // Se chegou aqui, COLUNA A está vazia, então é um item pertencente à categoria atual
                // Ajuste conforme sua planilha. Por exemplo:
                $name = $colC;     // nome do item
                $qtd = $colB;     // custo do item
                $unit = '';        // se precisar capturar unidade de alguma coluna
                $consumed = 0;     // se não tiver na planilha, pode definir zero ou outra lógica

                // Se não tiver nada em $name, significa linha em branco ou sem dados
                if (empty($name)) {
                    continue;
                }

                if(!is_numeric($qtd))
                $qtd = 0;
                
                $item = Item::create([
                    "name" => $name,
                    "cost" => 0,
                    "isFixed" => $fixedItemEnum, 
                    "category" => $currentCategory,
                    "consumed_per_client" => 0,
                    "unit" => 'unid'
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
        }
        echo "Importação concluída!\n";

    }
}
