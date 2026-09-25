<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\WatchController;
use App\Http\Middleware\TrackStorefrontVisit;
use Illuminate\Support\Facades\Route;

Route::middleware(TrackStorefrontVisit::class)->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('watches', [WatchController::class, 'index'])->name('watches.index');
    Route::get('watches/{watch:slug}', [WatchController::class, 'show'])->name('watches.show');
    Route::get('collections/{category:slug}', [WatchController::class, 'index'])->name('collections.show');
    Route::get('brands/{brand:slug}', [WatchController::class, 'index'])->name('brands.show');
    Route::get('about', [PageController::class, 'about'])->name('about');
    Route::get('contact', [PageController::class, 'contact'])->name('contact');
});

Route::post('inquiries', [InquiryController::class, 'store'])
    ->middleware('throttle:30,1')
    ->name('inquiries.store');

Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('dashboard', Admin\DashboardController::class)->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('watches', Admin\WatchController::class)->except('show');
        Route::patch('watches/{watch}/attributes', [Admin\WatchAttributeController::class, 'update'])
            ->name('watches.attributes.update');

        Route::scopeBindings()->group(function () {
            Route::post('watches/{watch}/images', [Admin\WatchImageController::class, 'store'])->name('watches.images.store');
            Route::put('watches/{watch}/images/order', [Admin\WatchImageController::class, 'reorder'])->name('watches.images.reorder');
            Route::patch('watches/{watch}/images/{image}', [Admin\WatchImageController::class, 'update'])->name('watches.images.update');
            Route::delete('watches/{watch}/images/{image}', [Admin\WatchImageController::class, 'destroy'])->name('watches.images.destroy');
        });

        Route::resource('categories', Admin\CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('brands', Admin\BrandController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::get('settings', [Admin\SiteSettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [Admin\SiteSettingController::class, 'update'])->name('settings.update');
    });
});

require __DIR__.'/settings.php';
