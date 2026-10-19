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
        Schema::create('guests', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('hotel_id');
    $table->string('name');
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->string('national_id')->nullable(); // رقم الهوية / الإقامة
    $table->text('notes')->nullable();
    $table->timestamps();
});


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
