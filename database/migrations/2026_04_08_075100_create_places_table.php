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
        Schema::create('places', function (Blueprint $table) {
            $table->id();

            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->foreignId('place_type_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->decimal('cost', 10, 2)->nullable();
            $table->integer('duration_minutes')->default(60);

            $table->enum('activity_level', ['خفيف', 'متوسط', 'متعب'])->default('متوسط');

            $table->float('average_rating')->default(0);
            $table->integer('reviews_count')->default(0);

            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();

            $table->boolean('is_outdoor')->default(false);

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
