<?php

declare(strict_types=1);

use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Api\V1\Auth\MobileAuthController;
use App\Http\Controllers\Api\V1\Marketplace\MarketplaceSearchController;
use App\Http\Controllers\Api\V1\Reservations\ReservationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->as('api.v1.')->group(function (): void {
    Route::get('marketplace/search', MarketplaceSearchController::class)->middleware('throttle:api')->name('marketplace.search');

    Route::prefix('auth')->as('auth.')->middleware('throttle:auth')->group(function (): void {
        Route::post('mobile/login', [MobileAuthController::class, 'login'])->name('mobile.login');
    });

    Route::middleware(['auth:api', 'throttle:api'])->group(function (): void {
        Route::post('auth/mobile/refresh', [MobileAuthController::class, 'refresh'])->name('auth.mobile.refresh');
        Route::post('auth/mobile/logout', [MobileAuthController::class, 'logout'])->name('auth.mobile.logout');
        Route::get('me', [MobileAuthController::class, 'me'])->name('me');
        Route::post('reservations', [ReservationController::class, 'store'])->name('reservations.store');
        Route::get('reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');

        Route::middleware('tenant')->prefix('company')->as('company.')->group(function (): void {
            Route::get('context', function () {
                $company = app(TenantContext::class)->company();

                return response()->json([
                    'data' => [
                        'id' => $company->getKey(),
                        'uuid' => $company->uuid,
                        'name' => $company->display_name,
                        'timezone' => $company->timezone,
                        'currency' => $company->currency,
                    ],
                ]);
            })->name('context');
        });
    });
});
