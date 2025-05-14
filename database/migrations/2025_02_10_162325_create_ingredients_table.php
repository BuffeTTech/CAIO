<?php

use App\Enums\IngredientSourceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\UnitEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->enum('unit', array_column(UnitEnum::cases(),'name'));
            $table->enum('source_type', array_column(IngredientSourceType::cases(),'name'));
            $table->string('ingredient_source');
            $table->string('observation')->nullable();
            $table->float('quantity'); // QUANTIDADE NO ESTOQUE
            $table->softDeletes('deleted_at', precision: 0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
