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
        Schema::table('invoices', function (Blueprint $table) {
    $table->id();
 $table->unsignedBigInteger('hotel_id');
    $table->unsignedBigInteger('booking_id');
    $table->string('invoice_number')->unique()->nullable();
   
    $table->unsignedBigInteger('room_id');
    $table->decimal('amount', 10, 2);
    $table->decimal('tax', 10, 2)->default(0);
    $table->decimal('total', 10, 2);
    $table->timestamps();

    // هذه العلاقة مسموحة لأنها لا تسبب مسار حذف مزدوج
    $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');

    // هذه يجب أن تكون بدون cascade
    $table->foreign('room_id')->references('id')->on('rooms')->onDelete('NO ACTION');
});


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
