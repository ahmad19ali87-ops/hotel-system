<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::create('cleanings', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('hotel_id');
    $table->unsignedBigInteger('room_id');
    $table->string('status')->default('pending'); // pending, in_progress, done
    $table->date('requested_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});

}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cleaning_logs');
    }
};
