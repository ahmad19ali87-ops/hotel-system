<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Users table additions
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'verification_code')) {
                    $table->string('verification_code', 10)->nullable()->after('remember_token');
                }
                if (!Schema::hasColumn('users', 'is_verified')) {
                    $table->boolean('is_verified')->default(false)->after('verification_code');
                }
                if (!Schema::hasColumn('users', 'first_name')) {
                    $table->string('first_name', 100)->nullable()->after('name');
                }
                if (!Schema::hasColumn('users', 'father_name')) {
                    $table->string('father_name', 100)->nullable()->after('first_name');
                }
                if (!Schema::hasColumn('users', 'last_name')) {
                    $table->string('last_name', 100)->nullable()->after('father_name');
                }
                if (!Schema::hasColumn('users', 'nationality')) {
                    $table->string('nationality', 100)->nullable()->default('سوري')->after('last_name');
                }
                if (!Schema::hasColumn('users', 'birth_date')) {
                    $table->date('birth_date')->nullable()->after('nationality');
                }
                if (!Schema::hasColumn('users', 'id_type')) {
                    $table->string('id_type', 50)->nullable()->default('national_id')->after('birth_date');
                }
                if (!Schema::hasColumn('users', 'id_number')) {
                    $table->string('id_number', 100)->nullable()->after('id_type');
                }
                if (!Schema::hasColumn('users', 'national_id')) {
                    $table->string('national_id', 100)->nullable()->after('id_number');
                }
                if (!Schema::hasColumn('users', 'address')) {
                    $table->string('address', 255)->nullable()->after('national_id');
                }
                if (!Schema::hasColumn('users', 'attachments')) {
                    $table->text('attachments')->nullable()->after('address');
                }
                if (!Schema::hasColumn('users', 'google_id')) {
                    $table->string('google_id', 255)->nullable()->after('email');
                }
            });
        }

        // 2. Guests table link with User
        if (Schema::hasTable('guests')) {
            Schema::table('guests', function (Blueprint $table) {
                if (!Schema::hasColumn('guests', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->nullable()->after('hotel_id');
                }
                if (!Schema::hasColumn('guests', 'address')) {
                    $table->string('address', 255)->nullable()->after('email');
                }
            });
        }

        // 3. Hotels table visibility toggle
        if (Schema::hasTable('hotels')) {
            Schema::table('hotels', function (Blueprint $table) {
                if (!Schema::hasColumn('hotels', 'is_visible')) {
                    $table->boolean('is_visible')->default(true)->after('stars');
                }
            });
        }

        // 4. Hotel Offers table
        if (!Schema::hasTable('offers')) {
            Schema::create('offers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('hotel_id');
                $table->string('title', 200);
                $table->text('description')->nullable();
                $table->decimal('discount_percentage', 5, 2)->nullable()->default(0);
                $table->decimal('special_price', 12, 2)->nullable();
                $table->decimal('special_price_usd', 10, 2)->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('image', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 5. Messages table enhancements (attachment, hotel_id, guest_id)
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                if (!Schema::hasColumn('messages', 'hotel_id')) {
                    $table->unsignedBigInteger('hotel_id')->nullable()->after('receiver_id');
                }
                if (!Schema::hasColumn('messages', 'guest_id')) {
                    $table->unsignedBigInteger('guest_id')->nullable()->after('hotel_id');
                }
                if (!Schema::hasColumn('messages', 'attachment')) {
                    $table->string('attachment', 255)->nullable()->after('body');
                }
                if (!Schema::hasColumn('messages', 'sender_type')) {
                    $table->string('sender_type', 50)->default('user')->after('attachment');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};

