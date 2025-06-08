<?php

use App\Enums\EventType;
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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade'); 
            $table->foreignId('menu_id')->constrained()->onDelete('cascade');
            $table->foreignId('address_id')->onDelete('cascade');
            $table->enum('type', array_column(EventType::cases(),'name'));
            $table->integer('guests_amount');
            $table->string('observation')->nullable();
            // $table->integer('staff_amount');
            // $table->float('staff_value');
            $table->date('date'); 
            $table->time('time'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
