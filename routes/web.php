<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Api;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\InquiryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public pages
|--------------------------------------------------------------------------
*/
Route::view('/', 'landing')->name('home');
Route::view('/inquiry', 'inquiry')->name('inquiry');
Route::get('/login', fn () => view('landing', ['openLogin' => true]))->middleware('guest')->name('login');

// Old static URLs keep working after the move to Laravel.
Route::redirect('/index.html', '/', 301);
Route::redirect('/app.html', '/app', 301);
Route::redirect('/inquiry.html', '/inquiry', 301);

Route::post('/login', [LoginController::class, 'store'])->middleware(['guest', 'throttle:20,1'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
Route::post('/inquiry', [InquiryController::class, 'store'])->middleware('throttle:5,1')->name('inquiry.store');

Route::get('/invitation/{token}', [InvitationController::class, 'show'])->middleware('throttle:30,1')->name('invitation.show');
Route::post('/invitation/{token}', [InvitationController::class, 'accept'])->middleware('throttle:10,1')->name('invitation.accept');

/*
|--------------------------------------------------------------------------
| POS application (signed-in business members)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'business'])->group(function () {
    Route::view('/app', 'pos.app')->name('app');

    Route::prefix('api')->middleware('throttle:240,1')->group(function () {
        Route::get('/bootstrap', Api\BootstrapController::class);

        Route::post('/sales', [Api\SaleController::class, 'store']);
        Route::put('/sales/{sale}', [Api\SaleController::class, 'update']);
        Route::delete('/sales/{sale}', [Api\SaleController::class, 'destroy']);
        Route::post('/sales/{sale}/returns', [Api\SaleController::class, 'storeReturn']);

        Route::post('/purchases', [Api\PurchaseController::class, 'store']);
        Route::put('/purchases/{purchase}', [Api\PurchaseController::class, 'update']);
        Route::delete('/purchases/{purchase}', [Api\PurchaseController::class, 'destroy']);
        Route::post('/purchases/{purchase}/returns', [Api\PurchaseController::class, 'storeReturn']);

        Route::post('/registers', [Api\RegisterController::class, 'store']);
        Route::post('/registers/{register}/close', [Api\RegisterController::class, 'close']);

        Route::post('/expenses', [Api\ExpenseController::class, 'store']);
        Route::put('/expenses/{expense}', [Api\ExpenseController::class, 'update']);
        Route::delete('/expenses/{expense}', [Api\ExpenseController::class, 'destroy']);
        Route::post('/expense-categories', [Api\ExpenseController::class, 'storeCategory']);
        Route::put('/expense-categories/{category}', [Api\ExpenseController::class, 'updateCategory']);
        Route::delete('/expense-categories/{category}', [Api\ExpenseController::class, 'destroyCategory']);

        Route::post('/products', [Api\ProductController::class, 'store']);
        Route::post('/products/quick', [Api\ProductController::class, 'quick']);
        Route::put('/products/prices', [Api\ProductController::class, 'prices']);
        Route::put('/products/{product}', [Api\ProductController::class, 'update']);
        Route::post('/products/{product}/adjust-stock', [Api\ProductController::class, 'adjustStock']);
        Route::post('/products/{product}/image', [Api\ProductController::class, 'uploadImage']);
        Route::delete('/products/{product}/image', [Api\ProductController::class, 'removeImage']);
        Route::post('/master/{kind}', [Api\ProductController::class, 'masterValue'])->whereIn('kind', ['category', 'unit']);

        Route::post('/customers', [Api\ContactController::class, 'storeCustomer']);
        Route::put('/customers/{customer}', [Api\ContactController::class, 'updateCustomer']);
        Route::delete('/customers/{customer}', [Api\ContactController::class, 'destroyCustomer']);
        Route::post('/suppliers', [Api\ContactController::class, 'storeSupplier']);
        Route::put('/suppliers/{supplier}', [Api\ContactController::class, 'updateSupplier']);
        Route::delete('/suppliers/{supplier}', [Api\ContactController::class, 'destroySupplier']);

        Route::post('/shipments', [Api\ShipmentController::class, 'store']);
        Route::put('/shipments/{shipment}', [Api\ShipmentController::class, 'update']);
        Route::put('/shipments/{shipment}/status', [Api\ShipmentController::class, 'status']);
        Route::put('/shipments/{shipment}/sale', [Api\ShipmentController::class, 'link']);
        Route::delete('/shipments/{shipment}', [Api\ShipmentController::class, 'destroy']);

        Route::post('/staff/invitations', [Api\StaffController::class, 'invite']);
        Route::put('/staff/{member}', [Api\StaffController::class, 'update']);
        Route::post('/staff/remove', [Api\StaffController::class, 'destroy']);

        Route::post('/quotations', [Api\QuotationController::class, 'store']);

        Route::put('/settings', [Api\SettingsController::class, 'update']);
    });
});

/*
|--------------------------------------------------------------------------
| EazyPOS team console
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'super-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::post('/businesses', [AdminController::class, 'storeBusiness'])->name('businesses.store');
    Route::put('/businesses/{business}/status', [AdminController::class, 'updateStatus'])->name('businesses.status');
    Route::put('/businesses/{business}/password', [AdminController::class, 'resetOwnerPassword'])->name('businesses.password');
    Route::put('/inquiries/{inquiry}', [AdminController::class, 'updateInquiry'])->name('inquiries.update');
});
