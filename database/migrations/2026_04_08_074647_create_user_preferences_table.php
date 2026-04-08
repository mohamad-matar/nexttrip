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
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->json('interests')->nullable(); // ["أثري","مقاهي"]
            $table->enum('trip_pace', ['بطيء', 'متوسط', 'مكثف'])->default('متوسط');

            $table->integer('budget_min')->nullable();
            $table->integer('budget_max')->nullable();

            $table->enum('preferred_activity_level', ['خفيف', 'متوسط', 'متعب'])->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
