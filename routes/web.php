<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Webhooks (public, no auth)
Route::get('/webhook/meta', [WebhookController::class, 'verify']);
Route::post('/webhook/meta', [WebhookController::class, 'handle'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

Route::get('/webhook/whatsapp', [WebhookController::class, 'verifyWhatsApp']);
Route::post('/webhook/whatsapp', [WebhookController::class, 'handleWhatsApp'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', fn () => Inertia::render('Dashboard/Index'));
    Route::get('/leads', fn () => Inertia::render('Leads/Index'));
    Route::get('/leads/{id}', fn (string $id) => Inertia::render('Leads/Show', ['id' => $id]));
    Route::get('/campaigns', fn () => Inertia::render('Campaigns/Index'));
    Route::get('/agent', fn () => Inertia::render('Agent/Monitoring'));
    Route::get('/analytics', fn () => Inertia::render('Analytics/Index'));
    Route::get('/knowledge-base', fn () => Inertia::render('KnowledgeBase/Index'));
    Route::get('/settings', fn () => Inertia::render('Settings/Index'));
});
