<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthSecurityAndResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('فندق النخبة');
            $table->string('slug')->default('elite-hotel');
            $table->unsignedTinyInteger('stars')->default(5);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('guest');
            $table->foreignId('hotel_id')->nullable();
            $table->string('avatar')->nullable();
            $table->string('phone')->nullable();
            $table->text('bio')->nullable();
            $table->string('job_title')->nullable();
            $table->text('permissions')->nullable();
            $table->string('verification_code', 10)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('first_name')->nullable();
            $table->string('father_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('nationality')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('id_type')->nullable();
            $table->string('id_number')->nullable();
            $table->string('national_id')->nullable();
            $table->string('address')->nullable();
            $table->text('attachments')->nullable();
            $table->string('google_id')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_registration_does_not_leak_code_in_session_or_view(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'name' => 'Ahmad Test',
            'email' => 'guest@example.com',
            'phone' => '0912345678',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect('/verify-code');

        $user = User::where('email', 'guest@example.com')->first();
        $this->assertNotNull($user);
        $code = $user->verification_code;
        $this->assertNotNull($code);

        // Flash message should NOT leak the raw verification code
        $flashSuccess = session('success');
        $this->assertStringNotContainsString($code, $flashSuccess);

        // Verify view does NOT leak the code in HTML or pre-fill
        $viewResponse = $this->get('/verify-code');
        $viewResponse->assertStatus(200);
        $viewResponse->assertDontSee("رمز التفعيل السريع");
        $viewResponse->assertDontSee('value="' . $code . '"', false);
    }

    public function test_forgot_password_rejects_unregistered_email(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => 'unregistered@example.com',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_registered_customer_can_request_password_reset_and_reset_it(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Registered Customer',
            'email' => 'customer@example.com',
            'password' => Hash::make('oldpassword'),
            'role' => 'guest',
            'is_verified' => true,
        ]);

        $response = $this->post('/forgot-password', [
            'email' => 'customer@example.com',
        ]);

        $response->assertSessionHas('status');

        // Check password_reset_tokens table
        $record = DB::table('password_reset_tokens')
            ->where('email', 'customer@example.com')
            ->first();
        $this->assertNotNull($record);

        $parts = explode('|', $record->token);
        $token = $parts[0];
        $code = $parts[1];

        // Reset password using token
        $resetResponse = $this->post('/reset-password', [
            'email' => 'customer@example.com',
            'token' => $token,
            'password' => 'newsecret123',
            'password_confirmation' => 'newsecret123',
        ]);

        $resetResponse->assertRedirect('/');

        $user->refresh();
        $this->assertTrue(Hash::check('newsecret123', $user->password));
    }

    public function test_api_forgot_password_and_reset_password_flow(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Mobile Guest',
            'email' => 'mobileguest@example.com',
            'password' => Hash::make('initialpass'),
            'role' => 'guest',
            'is_verified' => true,
        ]);

        // 1. API Forgot password request
        $forgotRes = $this->postJson('/api/auth/forgot-password', [
            'email' => 'mobileguest@example.com',
        ]);
        $forgotRes->assertStatus(200)
                  ->assertJson(['status' => 'success']);

        $record = DB::table('password_reset_tokens')
            ->where('email', 'mobileguest@example.com')
            ->first();
        $this->assertNotNull($record);
        $parts = explode('|', $record->token);
        $code = $parts[1];

        // 2. API Reset password with 6-digit code
        $resetRes = $this->postJson('/api/auth/reset-password', [
            'email' => 'mobileguest@example.com',
            'code' => $code,
            'password' => 'newmobilepass123',
            'password_confirmation' => 'newmobilepass123',
        ]);
        $resetRes->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        $user->refresh();
        $this->assertTrue(Hash::check('newmobilepass123', $user->password));
    }

    public function test_google_auth_redirects_with_client_id(): void
    {
        $response = $this->get('/auth/google');
        $response->assertRedirect();
        $targetUrl = $response->headers->get('Location');
        $this->assertStringContainsString('accounts.google.com', $targetUrl);
        $this->assertStringContainsString('467934742059-hnkanpf5954jqgtu18aubhqpj780et00.apps.googleusercontent.com', $targetUrl);
    }
}
