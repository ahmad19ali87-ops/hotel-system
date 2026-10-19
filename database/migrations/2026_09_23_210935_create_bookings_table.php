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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hotel_id');
            $table->unsignedBigInteger('room_id');
            $table->string('guest_name');
            $table->string('guest_phone');
            $table->date('check_in');
            $table->date('check_out');
            $table->decimal('total_price', 10, 2);
            $table->string('status')->default('confirmed'); // confirmed, cancelled
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
