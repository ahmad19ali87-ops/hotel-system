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
        Schema::create('deletion_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hotel_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('record_type'); // 'invoice' or 'booking'
            $table->unsignedBigInteger('record_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('guest_name')->nullable();
            $table->string('room_number')->nullable();
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('payment_status')->nullable();
            $table->text('reason');
            $table->longText('snapshot')->nullable();
            $table->timestamp('deleted_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deletion_logs');
    }
};
