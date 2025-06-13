<?php

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
        Schema::create('menu_event_item_has_matherials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_event_has_items_id')->constrained()->onDelete('cascade'); 
            $table->foreignId('matherial_id')->constrained()->onDelete('cascade'); 
            $table->dateTime('checked_at')->nullable()->default(null); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_event_item_has_matherials');
    }
};
