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
    Schema::create('payments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
        $table->string('transaction_id')->nullable();
        $table->string('payment_method')->nullable(); // stc, stripe, paypal...
        $table->decimal('amount', 10, 2);
        $table->string('status')->default('pending'); // pending / success / failed
        $table->timestamp('paid_at')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
