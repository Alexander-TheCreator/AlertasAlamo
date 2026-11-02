<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MunicipalDashboardController;

// Redirigir la raíz al Centro de Mando Municipal
Route::get('/', function () {
    return redirect()->route('municipal.dashboard');
});

// ==========================================
// FASE 1: PLATAFORMA WEB GOBIERNO MUNICIPAL
// ==========================================
Route::prefix('panel-municipal')->group(function () {
    Route::get('/', [MunicipalDashboardController::class, 'index'])->name('municipal.dashboard');
    Route::get('/api/poll', [MunicipalDashboardController::class, 'poll'])->name('municipal.poll');
    Route::post('/reportes/{id}/actualizar', [MunicipalDashboardController::class, 'updateStatus'])->name('municipal.update');
});