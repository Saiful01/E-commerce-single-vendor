<?php

use App\Http\Controllers\Admin\AbandonedCartController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CourierServiceController;
use App\Http\Controllers\Admin\CustomMessageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailConfigurationController;
use App\Http\Controllers\Admin\MessageTemplateController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\OrderPaymentController;
use App\Http\Controllers\Admin\OrderReturnController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\OrderStatusController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductReviewController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\SteadfastController;
use App\Http\Controllers\Admin\SmsGatewayController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WishlistController;
use App\Http\Controllers\AccountAddressController;
use App\Http\Controllers\AccountPaymentController;
use App\Http\Controllers\AccountReturnController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountOrderController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OnlinePaymentController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SslCommerzController;
use App\Http\Controllers\SteadfastWebhookController;
use App\Http\Controllers\StoreWishlistController;
use App\Http\Controllers\TrackOrderController;
use App\Http\Controllers\ProductController as StoreProductController; // Admin\ProductController er sathe naam conflict thakbe tai alias
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ==========================================
// Storefront (public) Routes
// ==========================================
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/product/{slug}', [StoreProductController::class, 'show'])->name('product.show');
Route::get('/product/{slug}/quick', [StoreProductController::class, 'quick'])->name('product.quick');   // AJAX quick view
Route::get('/search/suggest', [StoreProductController::class, 'suggest'])->name('search.suggest');       // AJAX live search
Route::post('/product/{product}/review', [StoreProductController::class, 'review'])
    ->middleware('auth')->name('product.review');

// Shop / listing
Route::get('/shop', [ShopController::class, 'index'])->name('shop');
Route::get('/category/{slug}', [ShopController::class, 'category'])->name('category.show');
Route::get('/brand/{slug}', [ShopController::class, 'brand'])->name('brand.show');

// Cart (AJAX, session based)
Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::get('/cart/summary', [CartController::class, 'summary'])->name('cart.summary');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');

// Checkout (guests can order too)
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
Route::post('/checkout/quote', [CheckoutController::class, 'quote'])->name('checkout.quote');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:20,1')->name('checkout.store');
Route::get('/order/success/{number}', [CheckoutController::class, 'success'])->name('order.success');
// SSLCommerz. success / fail / cancel / ipn are called by SSLCommerz, so they are left out of CSRF (see README)
Route::get('/payment/sslcommerz/pay/{number}', [SslCommerzController::class, 'pay'])->name('payment.sslcommerz.pay');
Route::match(['get', 'post'], '/payment/sslcommerz/success', [SslCommerzController::class, 'success'])->name('payment.sslcommerz.success');
Route::match(['get', 'post'], '/payment/sslcommerz/fail', [SslCommerzController::class, 'fail'])->name('payment.sslcommerz.fail');
Route::match(['get', 'post'], '/payment/sslcommerz/cancel', [SslCommerzController::class, 'cancel'])->name('payment.sslcommerz.cancel');
Route::post('/payment/sslcommerz/ipn', [SslCommerzController::class, 'ipn'])->name('payment.sslcommerz.ipn');

// Steadfast calls this when a parcel changes status (left out of CSRF, see README)
Route::post('/steadfast/webhook', [SteadfastWebhookController::class, 'handle'])->name('steadfast.webhook');

// bKash and Nagad: the customer is sent back with a normal GET link, so no CSRF change is needed
Route::get('/payment/bkash/pay/{number}', [OnlinePaymentController::class, 'pay'])->defaults('provider', 'bkash')->name('payment.bkash.pay');
Route::get('/payment/bkash/callback/{tran}', [OnlinePaymentController::class, 'callback'])->defaults('provider', 'bkash')->name('payment.bkash.callback');
Route::get('/payment/nagad/pay/{number}', [OnlinePaymentController::class, 'pay'])->defaults('provider', 'nagad')->name('payment.nagad.pay');
Route::get('/payment/nagad/callback/{tran}', [OnlinePaymentController::class, 'callback'])->defaults('provider', 'nagad')->name('payment.nagad.callback');

Route::get('/track-order', [TrackOrderController::class, 'index'])->middleware('throttle:20,1')->name('order.track');

// Pages written in the admin panel (about us, terms, return policy, FAQ, contact ...)
Route::get('/pages/{slug}', [PageController::class, 'show'])->name('pages.show');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,10')->name('contact.store');

// Wishlist + customer panel (login needed)
Route::middleware('auth')->group(function () {
    Route::get('/wishlist', [StoreWishlistController::class, 'index'])->name('wishlist');
    Route::post('/wishlist/toggle', [StoreWishlistController::class, 'toggle'])->name('wishlist.toggle');

    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
        Route::patch('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');

        Route::get('/orders', [AccountOrderController::class, 'index'])->name('orders');
        Route::get('/orders/{number}', [AccountOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{number}/cancel', [AccountOrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('/orders/{number}/reorder', [AccountOrderController::class, 'reorder'])->name('orders.reorder');
        Route::post('/orders/{number}/pay', [AccountPaymentController::class, 'store'])->name('orders.pay');
        Route::get('/orders/{number}/return', [AccountReturnController::class, 'create'])->name('orders.return');
        Route::post('/orders/{number}/return', [AccountReturnController::class, 'store'])->name('orders.return.store');

        Route::get('/addresses', [AccountAddressController::class, 'index'])->name('addresses');
        Route::post('/addresses', [AccountAddressController::class, 'store'])->name('addresses.store');
        Route::put('/addresses/{id}', [AccountAddressController::class, 'update'])->name('addresses.update');
        Route::patch('/addresses/{id}/default', [AccountAddressController::class, 'makeDefault'])->name('addresses.default');
        Route::delete('/addresses/{id}', [AccountAddressController::class, 'destroy'])->name('addresses.destroy');
    });
});

// Common Dashboard Route (Auto Redirects Based on User Type)
Route::get('/dashboard', function () {
    if (Auth::user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('account.dashboard'); // Customer panel
})->middleware(['auth', 'verified'])->name('dashboard');

// Customer / Authenticated User Profile Routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ==========================================
// Admin Panel Protected Routes
// ==========================================
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Admin Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Role & Permission CRUD Routes
    Route::resource('roles', RoleController::class);
    Route::patch('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
    Route::resource('users', UserController::class)->except('show');
    Route::patch('categories/{category}/toggle', [CategoryController::class, 'toggle'])->name('categories.toggle');
    Route::resource('categories', CategoryController::class)->except('show');
    Route::patch('brands/{brand}/toggle', [BrandController::class, 'toggle'])->name('brands.toggle');
    Route::resource('brands', BrandController::class)->except('show');

    Route::resource('products', ProductController::class)->except('show');
    Route::delete('product-images/{image}', [ProductController::class, 'destroyImage'])
        ->name('product-images.destroy');

    Route::post('sliders/reorder', [SliderController::class, 'reorder'])->name('sliders.reorder');
    Route::patch('sliders/{slider}/toggle', [SliderController::class, 'toggle'])->name('sliders.toggle');
    Route::resource('sliders', SliderController::class)->except('show');

    // Coupons
    Route::patch('coupons/{coupon}/toggle', [CouponController::class, 'toggle'])->name('coupons.toggle');
    Route::post('coupons/{coupon}/duplicate', [CouponController::class, 'duplicate'])->name('coupons.duplicate');
    Route::resource('coupons', CouponController::class)->except('show');

    // Order statuses
    Route::post('order-statuses/reorder', [OrderStatusController::class, 'reorder'])->name('order-statuses.reorder');
    Route::patch('order-statuses/{order_status}/toggle', [OrderStatusController::class, 'toggle'])->name('order-statuses.toggle');
    Route::resource('order-statuses', OrderStatusController::class)->only(['index', 'store', 'update', 'destroy']);

    // Courier services
    Route::patch('courier-services/{courier_service}/toggle', [CourierServiceController::class, 'toggle'])->name('courier-services.toggle');
    Route::resource('courier-services', CourierServiceController::class)->only(['index', 'store', 'update', 'destroy']);

    // Payment gateways
    Route::post('payment-gateways/reorder', [PaymentGatewayController::class, 'reorder'])->name('payment-gateways.reorder');
    Route::patch('payment-gateways/{payment_gateway}/toggle', [PaymentGatewayController::class, 'toggle'])->name('payment-gateways.toggle');
    Route::resource('payment-gateways', PaymentGatewayController::class)->only(['index', 'store', 'update', 'destroy']);

    // Orders
    // Steadfast courier
    Route::post('orders/steadfast/bulk', [SteadfastController::class, 'bulk'])->name('orders.steadfast.bulk');
    Route::post('orders/{order}/steadfast/book', [SteadfastController::class, 'book'])->name('orders.steadfast.book');
    Route::post('orders/{order}/steadfast/refresh', [SteadfastController::class, 'refresh'])->name('orders.steadfast.refresh');

    Route::get('orders/products/search', [OrderController::class, 'productSearch'])->name('orders.products.search');
    Route::get('orders/customer-lookup', [OrderController::class, 'customerLookup'])->name('orders.customer-lookup');
    Route::get('orders/{order}/invoice', [OrderController::class, 'invoice'])->name('orders.invoice');
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('orders/{order}/shipping', [OrderController::class, 'updateShipping'])->name('orders.shipping');
    Route::patch('orders/{order}/note', [OrderController::class, 'updateNote'])->name('orders.note');
    Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('orders/customers/search', [OrderController::class, 'customerSearch'])->name('orders.customers.search');

    // Payments (an order can be paid in parts) and returns
    Route::post('orders/{order}/payments', [OrderPaymentController::class, 'store'])->name('orders.payments.store');
    Route::patch('order-payments/{payment}/confirm', [OrderPaymentController::class, 'confirm'])->name('order-payments.confirm');
    Route::patch('order-payments/{payment}/reject', [OrderPaymentController::class, 'reject'])->name('order-payments.reject');

    Route::get('orders/{order}/returns/create', [OrderReturnController::class, 'create'])->name('orders.returns.create');
    Route::post('orders/{order}/returns', [OrderReturnController::class, 'store'])->name('orders.returns.store');
    Route::get('order-returns', [OrderReturnController::class, 'index'])->name('order-returns.index');
    Route::get('order-returns/{orderReturn}', [OrderReturnController::class, 'show'])->name('order-returns.show');
    Route::patch('order-returns/{orderReturn}/approve', [OrderReturnController::class, 'approve'])->name('order-returns.approve');
    Route::patch('order-returns/{orderReturn}/reject', [OrderReturnController::class, 'reject'])->name('order-returns.reject');
    Route::patch('order-returns/{orderReturn}/complete', [OrderReturnController::class, 'complete'])->name('order-returns.complete');

    // View only
    Route::resource('wishlists', WishlistController::class)->only(['index', 'show']);
    Route::resource('abandoned-carts', AbandonedCartController::class)->only(['index', 'show']);
    Route::post('abandoned-carts/{abandonedCart}/remind', [AbandonedCartController::class, 'remind'])->name('abandoned-carts.remind'); // optional

    // View + moderate
    Route::resource('product-reviews', ProductReviewController::class)->only(['index', 'show', 'destroy']);
    Route::patch('product-reviews/{productReview}/status', [ProductReviewController::class, 'updateStatus'])->name('product-reviews.status');

    // Full CRUD
    Route::resource('message-templates', MessageTemplateController::class);
    Route::resource('email-configurations', EmailConfigurationController::class);
    Route::resource('sms-gateways', SmsGatewayController::class);
    Route::resource('custom-messages', CustomMessageController::class);

    // Pages and contact form messages
    Route::patch('pages/{page}/toggle', [AdminPageController::class, 'toggle'])->name('pages.toggle');
    Route::resource('pages', AdminPageController::class)->except('show');
    Route::patch('contact-messages/{contactMessage}/read', [ContactMessageController::class, 'toggleRead'])->name('contact-messages.read');
    Route::resource('contact-messages', ContactMessageController::class)->only(['index', 'show', 'destroy']);

    // Site Settings (single row, so only edit + update)
    Route::get('settings', [SiteSettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SiteSettingController::class, 'update'])->name('settings.update');
});

// Authentication Routes (Breeze)
require __DIR__.'/auth.php';
