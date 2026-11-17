<?php

use App\Http\Controllers\WithdrawalController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', fn () => redirect()->route('filament.admin.auth.login'))->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/retrait', [WithdrawalController::class, 'create'])->name('withdrawal.create');
    Route::post('/retrait', [WithdrawalController::class, 'store'])->name('withdrawal.store');
});
