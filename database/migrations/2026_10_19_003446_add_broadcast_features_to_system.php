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
        Schema::create('app_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40)->default('message'); // 'message' or 'event_notification'
            $table->string('title', 200);
            $table->text('body');
            $table->string('target', 50)->default('guests'); // 'guests', 'everyone', 'staff'
            $table->string('event_type', 50)->nullable(); // 'general', 'promo', 'urgent', 'maintenance', 'special_event'
            $table->string('icon', 20)->nullable()->default('📢');
            $table->string('link', 300)->nullable();
            $table->boolean('allow_reply')->default(false);
            $table->unsignedInteger('recipients_count')->default(0);
            $table->timestamps();
        });

        Schema::table('messages', function (Blueprint $table) {
            if (!Schema::hasColumn('messages', 'is_broadcast')) {
                $table->boolean('is_broadcast')->default(false)->after('sender_type');
            }
            if (!Schema::hasColumn('messages', 'allow_reply')) {
                $table->boolean('allow_reply')->default(true)->after('is_broadcast');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_broadcasts');

        Schema::table('messages', function (Blueprint $table) {
            if (Schema::hasColumn('messages', 'allow_reply')) {
                $table->dropColumn('allow_reply');
            }
            if (Schema::hasColumn('messages', 'is_broadcast')) {
                $table->dropColumn('is_broadcast');
            }
        });
    }
};
