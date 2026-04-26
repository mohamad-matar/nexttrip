<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guide_bookings', function (Blueprint $table) {
            $table->id();            

            // السائح الذي قام بالحجز
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->foreignId('guide_id')->constrained()->cascadeOnDelete();

            // ربط الحجز برحلة معينة (اختياري)
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();

            $table->date('book_date'); //تاريخ البدء
            $table->date('day_count'); //عدد الأيام

            $table->enum('status', ['pending', 'accepted', 'rejected', 'canceled'])
                  ->default('pending');

            $table->text('description'); //وصف عملية الحجز يضعه السائح
            $table->string('guide_note'); //يمكن هنا ذكر سبب الرفض أو ملاحظات عند القبول 

            $table->timestamps();

            // منع حجز المرشد مرتين في نفس اليوم
            $table->unique(['guide_id', 'book_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guide_bookings');
    }
};
