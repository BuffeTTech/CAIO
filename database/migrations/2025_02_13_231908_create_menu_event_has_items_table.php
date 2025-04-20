<?php

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
        Schema::create('menu_event_has_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_event_id')->constrained()->onDelete('cascade'); 
            $table->foreignId('item_id')->constrained()->onDelete('cascade'); 
            $table->dateTime('checked_at')->nullable()->default(null); 
            $table->double('cost');

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
        Schema::dropIfExists('menu_event_has_items');
    }
};
