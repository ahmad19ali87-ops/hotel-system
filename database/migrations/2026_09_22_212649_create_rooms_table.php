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
    Schema::create('rooms', function (Blueprint $table) {
        $table->id();
$table->unsignedBigInteger('hotel_id');
$table->foreign('hotel_id')->references('id')->on('hotels')->onDelete('cascade');

        // رقم الغرفة
        $table->string('room_number');

        // نوع الغرفة (مفردة – مزدوجة – جناح)
        $table->unsignedBigInteger('room_type_id');

        // السعر الليلي
        $table->decimal('price', 10, 2);

        // حالة الغرفة (متاحة – محجوزة – صيانة)
        $table->string('status')->default('available');

        $table->timestamps();

        // العلاقة مع جدول RoomTypes
        $table->foreign('room_type_id')->references('id')->on('room_types');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
