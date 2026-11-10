<?php

use App\Http\Controllers as C;
use App\Http\Controllers\Admin as A;
use App\Http\Controllers\Auth as AuthC;
use Illuminate\Support\Facades\Route;

/* ----------------------------- Storefront ----------------------------- */
Route::get('/storage/{path}', [C\MediaController::class, 'show'])->where('path', '.*')->name('media');
Route::get('/', [C\HomeController::class, 'index'])->name('home');
Route::get('/about-us', [C\PageController::class, 'about'])->name('about');
Route::get('/page/{slug}', [C\PageController::class, 'show'])->name('page');

Route::get('/shop', [C\ShopController::class, 'index'])->name('shop');
Route::get('/product-category/{category:slug}', [C\ShopController::class, 'category'])->name('category');
Route::get('/product/{product:slug}', [C\ProductController::class, 'show'])->name('product');
Route::post('/product/{product:slug}/review', [C\ProductController::class, 'review'])->middleware('throttle:forms')->name('product.review');
Route::get('/search/suggest', [C\ShopController::class, 'suggest'])->middleware('throttle:search')->name('search.suggest');
Route::get('/try-on', [C\TryOnController::class, 'index'])->name('tryon');

Route::get('/cart', [C\CartController::class, 'index'])->name('cart');
Route::get('/cart/drawer', [C\CartController::class, 'drawer'])->name('cart.drawer');
Route::post('/cart/add', [C\CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/items/{item}', [C\CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/items/{item}', [C\CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/coupon', [C\CartController::class, 'coupon'])->middleware('throttle:coupon')->name('cart.coupon');
Route::delete('/cart/coupon', [C\CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

Route::get('/checkout', [C\CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [C\CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');
Route::get('/checkout/pay/{order}', [C\PaymentController::class, 'show'])->name('checkout.pay');
Route::post('/checkout/pay/{order}/verify', [C\PaymentController::class, 'verify'])->middleware('throttle:checkout')->name('checkout.verify');
Route::post('/checkout/pay/{order}/failed', [C\PaymentController::class, 'failed'])->middleware('throttle:checkout')->name('checkout.failed');
Route::post('/checkout/pay/{order}/cancel', [C\PaymentController::class, 'cancel'])->name('checkout.cancel');
Route::get('/order-received/{order}', [C\OrderReceivedController::class, 'show'])->name('order.received');
Route::post('/webhooks/razorpay', [C\PaymentController::class, 'webhook'])->name('webhooks.razorpay');

Route::get('/contact-us', [C\ContactController::class, 'show'])->name('contact');
Route::post('/contact-us', [C\ContactController::class, 'store'])->middleware('throttle:forms')->name('contact.store');
Route::get('/help-center', [C\HelpController::class, 'show'])->name('help');
Route::post('/help-center/feedback', [C\HelpController::class, 'feedback'])->middleware('throttle:forms')->name('help.feedback');
Route::post('/newsletter', [C\NewsletterController::class, 'subscribe'])->middleware('throttle:forms')->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [C\NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
Route::post('/chat', [C\ChatController::class, 'ask'])->middleware('throttle:search')->name('chat');

/* ----------------------------- Customer auth ----------------------------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthC\CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthC\CustomerAuthController::class, 'login'])->middleware('throttle:login')->name('login.store');
    Route::post('/register', [AuthC\CustomerAuthController::class, 'register'])->middleware('throttle:forms')->name('register');
    Route::get('/forgot-password', [AuthC\PasswordController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [AuthC\PasswordController::class, 'email'])->middleware('throttle:forms')->name('password.email');
    Route::get('/reset-password/{token}', [AuthC\PasswordController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [AuthC\PasswordController::class, 'update'])->name('password.update');
});
Route::post('/logout', [AuthC\CustomerAuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->prefix('my-account')->name('account.')->group(function () {
    Route::get('/', [C\AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/orders', [C\AccountController::class, 'orders'])->name('orders');
    Route::get('/orders/{order}', [C\AccountController::class, 'order'])->name('order');
    Route::get('/orders/{order}/receipt', [C\AccountController::class, 'receipt'])->name('order.receipt');
    Route::post('/orders/{order}/cancel', [C\AccountController::class, 'cancel'])->name('order.cancel');
    Route::get('/downloads', [C\AccountController::class, 'downloads'])->name('downloads');
    Route::get('/addresses', [C\AccountController::class, 'addresses'])->name('addresses');
    Route::post('/addresses', [C\AccountController::class, 'saveAddress'])->name('addresses.store');
    Route::put('/addresses/{address}', [C\AccountController::class, 'saveAddress'])->name('addresses.update');
    Route::delete('/addresses/{address}', [C\AccountController::class, 'deleteAddress'])->name('addresses.destroy');
    Route::get('/details', [C\AccountController::class, 'details'])->name('details');
    Route::put('/details', [C\AccountController::class, 'updateDetails'])->name('details.update');
});

/* ----------------------------- Admin ----------------------------- */
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthC\AdminAuthController::class, 'show'])->name('login')->middleware('guest');
    Route::post('/login', [AuthC\AdminAuthController::class, 'login'])->middleware('throttle:login')->name('login.store');
    Route::post('/logout', [AuthC\AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [A\DashboardController::class, 'index'])->middleware('perm:dashboard')->name('dashboard');

        Route::middleware('perm:products')->group(function () {
            Route::resource('products', A\ProductController::class)->except('show');
            Route::delete('products/{product}/images/{image}', [A\ProductController::class, 'destroyImage'])->name('products.images.destroy');
            Route::resource('reviews', A\ReviewController::class)->only(['index', 'update', 'destroy']);
        });
        Route::middleware('perm:catalog')->group(function () {
            Route::resource('categories', A\CategoryController::class)->except('show');
            Route::resource('brands', A\BrandController::class)->except('show');
        });

        Route::middleware('perm:orders')->group(function () {
            Route::get('orders', [A\OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [A\OrderController::class, 'show'])->name('orders.show');
            Route::post('orders/{order}/status', [A\OrderController::class, 'status'])->name('orders.status');
            Route::post('orders/{order}/tracking', [A\OrderController::class, 'tracking'])->name('orders.tracking');
            Route::post('orders/{order}/note', [A\OrderController::class, 'note'])->name('orders.note');
            Route::post('orders/{order}/refund', [A\OrderController::class, 'refund'])->name('orders.refund');
        });
        Route::middleware('perm:customers')->group(function () {
            Route::get('customers', [A\CustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/{user}', [A\CustomerController::class, 'show'])->name('customers.show');
            Route::post('customers/{user}/toggle', [A\CustomerController::class, 'toggle'])->name('customers.toggle');
        });
        Route::middleware('perm:coupons')->resource('coupons', A\CouponController::class)->except('show');

        Route::middleware('perm:content')->group(function () {
            Route::resource('hero-slides', A\HeroSlideController::class)->except('show');
            Route::resource('blocks', A\HomeBlockController::class)->except('show');
            Route::resource('pages', A\PageController::class)->except('show');
            Route::resource('faq-topics', A\FaqTopicController::class)->except('show');
            Route::resource('faqs', A\FaqController::class)->except('show');
        });
        Route::middleware('perm:newsletter')->group(function () {
            Route::get('newsletter', [A\NewsletterController::class, 'index'])->name('newsletter.index');
            Route::get('newsletter/export', [A\NewsletterController::class, 'export'])->name('newsletter.export');
            Route::post('newsletter/{subscriber}/toggle', [A\NewsletterController::class, 'toggle'])->name('newsletter.toggle');
            Route::post('newsletter/sync-omnisend', [A\NewsletterController::class, 'syncOmnisend'])->name('newsletter.sync');
        });
        Route::middleware('perm:messages')->group(function () {
            Route::get('messages', [A\MessageController::class, 'index'])->name('messages.index');
            Route::post('messages/{message}/status', [A\MessageController::class, 'status'])->name('messages.status');
        });
        Route::middleware('perm:reports')->group(function () {
            Route::get('reports', [A\ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/export/{type}', [A\ReportController::class, 'export'])->name('reports.export');
            Route::get('reports/popular', [A\ReportController::class, 'popular'])->name('reports.popular');
        });
        Route::middleware('perm:settings')->group(function () {
            Route::get('settings', [A\SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [A\SettingController::class, 'update'])->name('settings.update');
        });
        Route::middleware('perm:admin_users')->resource('users', A\AdminUserController::class)->except('show');
        Route::get('activity-log', [A\ActivityLogController::class, 'index'])->middleware('perm:activity_log')->name('activity.index');
    });
});
