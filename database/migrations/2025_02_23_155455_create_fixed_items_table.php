<?php
use App\Enums\FixedItemsCategory;
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
        Schema::create('fixed_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->double('qtd');
            $table->enum('category', array_column(FixedItemsCategory::cases(),'name'));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_items');
    }
};
