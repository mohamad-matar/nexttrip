<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

        // ربط مع جدول الوحدات


    public function up(): void
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();

            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            
            $table->string('name');
            $table->text('description')->nullable();
            
            $table->decimal('cost', 10, 2)->nullable();
            $table->foreignId('price_unit_id')->nullable()->constrained('price_units')->nullOnDelete();
            
            $table->integer('duration_minutes')->default(60);

            $table->enum('activity_level', ['خفيف', 'متوسط', 'متعب'])->default('متوسط');

            // حقول ذكية للفصول والأوقات (نستخدم JSON للمرونة)
            $table->boolean('is_outdoor')->default(false);
            $table->json('best_seasons')->nullable(); // ["spring", "summer"]
            $table->json('recommended_times')->nullable(); // ["morning", "evening"]
            $table->string('opening_hours')->nullable(); 
            
            $table->float('average_rating')->default(0);
            $table->integer('reviews_count')->default(0);

            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();


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
