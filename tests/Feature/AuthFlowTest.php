<?php

namespace Tests\Feature;

use App\Mail\WelcomeCustomer;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\MakesStore;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase, MakesStore;

    private function reg(array $o = []): array
    {
        return $o + ['name' => 'Asha Patil', 'email' => 'asha@test.test', 'password' => 'Password123', 'password_confirmation' => 'Password123'];
    }

    public function test_registration_creates_hashed_customer_signs_in_and_sends_welcome(): void
    {
        Mail::fake();
        $this->post(route('register'), $this->reg())->assertRedirect(route('account.dashboard'));
        $u = User::where('email', 'asha@test.test')->firstOrFail();
        $this->assertSame('customer', $u->role);
        $this->assertSame('Asha Patil', $u->name);
        $this->assertTrue(Hash::check('Password123', $u->password));
        $this->assertNotSame('Password123', $u->password);
        $this->assertAuthenticatedAs($u);
        Mail::assertSent(WelcomeCustomer::class, fn ($m) => $m->hasTo('asha@test.test'));
    }

    public function test_registration_validation_messages(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->customer(['email' => 'taken@test.test']);
        foreach ([
            [['name' => ''], 'name'], [['email' => 'nope'], 'email'], [['password' => 'short1', 'password_confirmation' => 'short1'], 'password'],
            [['password' => 'allletters', 'password_confirmation' => 'allletters'], 'password'], [['password_confirmation' => 'Different123'], 'password'],
            [['email' => 'taken@test.test'], 'email'], [['website' => 'bot'], 'website'],
        ] as [$bad, $field]) {
            $this->post(route('register'), $this->reg($bad))->assertSessionHasErrorsIn('register', $field);
        }
        $this->assertSame(1, User::count());
        $this->assertGuest();
    }

    public function test_registration_survives_mail_failure(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1]);
        $this->post(route('register'), $this->reg())->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'asha@test.test')->first());
    }

    public function test_guest_cart_follows_the_new_customer(): void
    {
        $p = $this->product();
        $guest = $this->guestCart([[$p, 2]], 'g-tok');
        $this->post(route('register'), $this->reg());
        $mine = Cart::where('user_id', User::first()->id)->first();
        $this->assertSame(2, (int) $mine->items()->sum('qty'));
        $this->assertNull(Cart::find($guest->id));
    }

    public function test_customer_returns_to_checkout_after_login_or_registration(): void
    {
        $this->customer(['email' => 'c@test.test']);
        $this->post(route('login.store'), ['username' => 'c@test.test', 'password' => 'Password123', 'redirect' => '/checkout'])->assertRedirect('/checkout');
        $this->post(route('logout'));
        $this->post(route('register'), $this->reg(['redirect' => '/checkout']))->assertRedirect('/checkout');
    }

    public function test_checkout_page_tells_the_login_form_to_return_to_checkout(): void
    {
        $this->guestCart([[$this->product(), 1]]);
        $this->get('/checkout')->assertOk()->assertSee('name="redirect" value="/checkout"', false);
    }

    public function test_open_redirects_are_ignored(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->customer(['email' => 'c@test.test']);
        foreach (['https://evil.test', '//evil.test', '/\\evil.test', 'javascript:alert(1)', '/login', '/logout'] as $bad) {
            $this->post(route('login.store'), ['username' => 'c@test.test', 'password' => 'Password123', 'redirect' => $bad])->assertRedirect(route('account.dashboard'));
            $this->post(route('logout'));
        }
    }

    public function test_remember_me_sets_the_cookie_only_when_asked(): void
    {
        $this->customer(['email' => 'c@test.test']);
        $names = fn ($r) => collect($r->headers->getCookies())->map->getName()->filter(fn ($n) => str_starts_with($n, 'remember_web_'))->count();
        $this->assertSame(1, $names($this->post(route('login.store'), ['username' => 'c@test.test', 'password' => 'Password123', 'remember' => 1])));
        $this->post(route('logout'));
        $this->assertSame(0, $names($this->post(route('login.store'), ['username' => 'c@test.test', 'password' => 'Password123'])));
    }

    public function test_logout_ends_the_session(): void
    {
        $u = $this->customer();
        $this->actingAs($u)->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
        $this->get('/my-account')->assertRedirect(route('login'));
    }

    public function test_password_reset_end_to_end(): void
    {
        Mail::fake();
        $u = $this->customer(['email' => 'reset@test.test']);
        $this->post(route('password.email'), ['email' => 'reset@test.test'])->assertSessionHas('success');
        $this->post(route('password.email'), ['email' => 'nobody@test.test'])->assertSessionHas('success'); // no account enumeration
        $token = Password::createToken($u);
        $this->get(route('password.reset', ['token' => $token, 'email' => $u->email]))->assertOk()->assertSee($token);
        $this->post(route('password.update'), ['token' => 'bad', 'email' => $u->email, 'password' => 'NewPassw0rd', 'password_confirmation' => 'NewPassw0rd'])->assertSessionHasErrors('email');
        $this->post(route('password.update'), ['token' => $token, 'email' => $u->email, 'password' => 'NewPassw0rd', 'password_confirmation' => 'NewPassw0rd'])->assertRedirect(route('login'));
        $this->post(route('login.store'), ['username' => 'reset@test.test', 'password' => 'NewPassw0rd'])->assertRedirect(route('account.dashboard'));
        $this->post(route('logout'));
        $this->post(route('password.update'), ['token' => $token, 'email' => $u->email, 'password' => 'Another1234', 'password_confirmation' => 'Another1234'])->assertSessionHasErrors('email'); // token is single-use
    }

    public function test_session_id_changes_on_login_and_register(): void
    {
        $this->customer(['email' => 'c@test.test']);
        $before = session()->getId();
        $this->post(route('login.store'), ['username' => 'c@test.test', 'password' => 'Password123']);
        $this->assertNotSame($before, session()->getId());
    }
}
