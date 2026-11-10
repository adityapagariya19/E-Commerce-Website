<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Setting;
use App\Services\CartService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CartService::class);
    }

    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(5)->by(strtolower((string) $r->input('email', $r->input('username'))).'|'.$r->ip()));
        RateLimiter::for('forms', fn (Request $r) => Limit::perMinute(5)->by($r->ip()));
        RateLimiter::for('checkout', fn (Request $r) => Limit::perMinute(12)->by($r->ip()));
        RateLimiter::for('coupon', fn (Request $r) => Limit::perMinute(10)->by($r->ip()));
        RateLimiter::for('search', fn (Request $r) => Limit::perMinute(60)->by($r->ip()));

        View::composer('layouts.store', function ($view) {
            $view->with([
                'navCategories' => Category::active()->whereNull('parent_id')->orderBy('position')->orderBy('name')->get(),
                'cartCount' => app(CartService::class)->count(),
            ]);
        });
    }
}
