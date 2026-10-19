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
        Schema::create('complaints', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('hotel_id');
    $table->unsignedBigInteger('guest_id')->nullable();
    $table->unsignedBigInteger('booking_id')->nullable();
    $table->unsignedBigInteger('room_id')->nullable();
    $table->string('title');
    $table->text('message');
    $table->string('status')->default('new'); // new, in_progress, closed
    $table->text('response')->nullable();    // رد الإدارة
    $table->unsignedBigInteger('handled_by')->nullable(); // الموظف الذي تعامل مع الشكوى
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
