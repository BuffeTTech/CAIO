<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('role_quantities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_information_id')->constrained('role_informations')->onDelete('cascade'); 
            $table->integer('guests_init');
            $table->integer('guests_end');
            $table->integer('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_quantities');
    }
};
