<?php

declare(strict_types=1);

use App\Http\Controllers\Web\WebAuthController;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'marketplace.home')->name('home');

Route::post('locale', function (Request $request): RedirectResponse {
    $data = $request->validate(['locale' => ['required', 'in:en,ar']]);
    $request->session()->put('locale', $data['locale']);

    return back();
})->name('locale');

Route::middleware('guest')->group(function (): void {
    Route::get('register', [WebAuthController::class, 'createRegistration'])->name('register');
    Route::post('register', [WebAuthController::class, 'register'])->name('register.store');
    Route::get('login', [WebAuthController::class, 'createSession'])->name('login');
    Route::post('login', [WebAuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('dashboard', function (Request $request) {
        $reservations = Reservation::query()
            ->withoutGlobalScopes()
            ->with('vehicleGroup')
            ->where('customer_id', $request->user()->getKey())
            ->latest()
            ->limit(10)
            ->get();

        return view('dashboard.customer', compact('reservations'));
    })->name('dashboard');
    Route::post('logout', [WebAuthController::class, 'logout'])->name('logout');
});
