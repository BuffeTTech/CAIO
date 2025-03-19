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
        Schema::create('menu_event_information', function (Blueprint $table) {
            $table->id();
            $table->float('price');
            $table->float('quantity'); // default value of itme
            $table->foreignId('menu_information_id')->constrained('menu_information')->onDelete('cascade'); // role information
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_event_information');
    }
};
