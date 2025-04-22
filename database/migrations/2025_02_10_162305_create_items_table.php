<?php

use App\Enums\FoodCategory;
use App\Enums\FoodProductionType;
use App\Enums\FoodType;
use App\Enums\UnitEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->double('cost');
            $table->enum('type', array_column(FoodType::cases(),'name'));
            $table->enum('category', array_column(FoodCategory::cases(),'name'));
            $table->enum('production_type',array_column(FoodProductionType::cases(),'name'));
            $table->float('consumed_per_client');
            $table->enum('unit', array_column(UnitEnum::cases(),'name'));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
