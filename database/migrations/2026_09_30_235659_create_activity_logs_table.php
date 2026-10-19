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
       Schema::create('activity_logs', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id')->nullable();
    $table->string('action'); // نوع العملية
    $table->string('model')->nullable(); // اسم الموديل
    $table->unsignedBigInteger('model_id')->nullable(); // رقم السجل
    $table->text('details')->nullable(); // تفاصيل إضافية
    $table->ipAddress('ip')->nullable();
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
