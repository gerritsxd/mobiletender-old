<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    public function test_login_page_links_to_register(): void
    {
        $this->get('/login')->assertStatus(200)->assertSee('/register');
    }

    public function test_register_grants_configured_role(): void
    {
        config(['customoptions.register_default_role' => 'manager']);
        $email = 'reg_' . Str::random(8) . '@test.local';

        $this->withoutMiddleware(VerifyCsrfToken::class)->post('/register', [
            'name' => 'Test Employee',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect();

        $user = User::where('email', $email)->first();
        self::assertNotNull($user);
        self::assertEquals('manager', $user->type);
        self::assertTrue($user->isManager());

        $user->delete();
    }

    public function test_register_grants_no_access_when_role_empty(): void
    {
        config(['customoptions.register_default_role' => '']);
        $email = 'reg_' . Str::random(8) . '@test.local';

        $this->withoutMiddleware(VerifyCsrfToken::class)->post('/register', [
            'name' => 'No Access',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect();

        $user = User::where('email', $email)->first();
        self::assertNotNull($user);
        self::assertFalse($user->isEmployee());

        $user->delete();
    }
}
