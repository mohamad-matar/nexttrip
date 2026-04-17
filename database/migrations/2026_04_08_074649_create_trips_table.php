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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            
            $table->integer('budget_max')->nullable();            
            $table->enum('trip_pace', ['بطيء', 'متوسط', 'مكثف'])->default('متوسط');
            $table->enum('preferred_activity_level', ['خفيف', 'متوسط', 'متعب'])->nullable();


            $table->integer('days');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->decimal('total_cost', 10, 2)->nullable();            

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
