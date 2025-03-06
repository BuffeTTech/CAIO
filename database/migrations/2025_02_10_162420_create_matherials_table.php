<?php

use App\Enums\MatherialType;
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
        Schema::create('matherials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('category', array_column(MatherialType::cases(),'name'));
            $table->float('quantity'); // QUANTIDADE NO ESTOQUE
            $table->string('observation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matherials');
    }
};
