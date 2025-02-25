<?php

use App\Enums\FoodCategory;
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
            $table->enum('type', array_column(FoodCategory::cases(),'name'));
            $table->enum('category', array_column(FoodCategory::cases(),'name'));
            $table->float('consumed_per_client');
            $table->string('unit');
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
