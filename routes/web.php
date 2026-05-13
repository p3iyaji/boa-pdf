<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompressController;
use App\Http\Controllers\ConvertController;
use App\Http\Controllers\MergeController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\SignatureController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('authenticate');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');

    Route::prefix('pdf')->name('pdf.')->group(function (): void {
        Route::get('/', [PdfController::class, 'index'])->name('index');
        Route::post('/', [PdfController::class, 'upload'])->name('upload');

        Route::get('/merge', [MergeController::class, 'create'])->name('merge.create');
        Route::post('/merge', [MergeController::class, 'store'])->name('merge.store');

        Route::get('/compress', [CompressController::class, 'create'])->name('compress.create');
        Route::post('/compress', [CompressController::class, 'store'])->name('compress.store');

        Route::get('/convert', [ConvertController::class, 'create'])->name('convert.create');
        Route::post('/convert', [ConvertController::class, 'store'])->name('convert.store');

        Route::get('/{document}', [PdfController::class, 'show'])->name('show');
        Route::get('/{document}/stream', [PdfController::class, 'stream'])->name('stream');
        Route::get('/{document}/download', [PdfController::class, 'download'])->name('download');
        Route::delete('/{document}', [PdfController::class, 'destroy'])->name('destroy');

        Route::get('/{document}/sign', [SignatureController::class, 'create'])->name('sign.create');
        Route::post('/{document}/sign', [SignatureController::class, 'store'])->name('sign.store');
    });
});
