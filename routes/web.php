<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Murid\LatihanController;
use App\Http\Controllers\Murid\PeringkatController;
use App\Http\Controllers\Murid\UjianController;
use App\Http\Controllers\SetupController;
use App\Livewire\Murid\LatihanAdaptif;
use App\Livewire\Murid\PengerjaanUjian;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('beranda'))->name('beranda');

// One-time installer. Both the controller and CreateFirstAdmin refuse once an
// admin exists, so leaving the route registered is harmless.
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/setup', [SetupController::class, 'show'])->name('setup.show');
    Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');
});

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [LoginController::class, 'show'])->name('masuk');

    // 5 attempts per minute per IP, per .claude/rules/security.md.
    Route::post('/masuk', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('masuk.store');

    Route::get('/lupa-kata-sandi', [PasswordResetController::class, 'request'])->name('lupa-kata-sandi');
    Route::post('/lupa-kata-sandi', [PasswordResetController::class, 'email'])
        ->middleware('throttle:5,1')
        ->name('lupa-kata-sandi.email');

    Route::get('/atur-ulang-kata-sandi/{token}', [PasswordResetController::class, 'reset'])
        ->name('atur-ulang-kata-sandi');
    Route::post('/atur-ulang-kata-sandi', [PasswordResetController::class, 'update'])
        ->middleware('throttle:5,1')
        ->name('atur-ulang-kata-sandi.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [LoginController::class, 'destroy'])->name('keluar');

    Route::get('/ganti-kata-sandi', [PasswordController::class, 'edit'])->name('ganti-kata-sandi');
    Route::put('/ganti-kata-sandi', [PasswordController::class, 'update'])->name('ganti-kata-sandi.update');

    Route::get('/ujian', [UjianController::class, 'index'])->name('ujian.index');

    Route::get('/peringkat', [PeringkatController::class, 'index'])->name('peringkat.index');

    Route::get('/latihan', [LatihanController::class, 'index'])->name('latihan.index');
    Route::get('/latihan/{subject}', LatihanAdaptif::class)
        ->middleware('throttle:60,1')
        ->name('latihan.mulai');

    // 60 requests a minute per student, per .claude/rules/security.md. The
    // limit sits on the exam screen because every saved answer is a request.
    Route::get('/ujian/{exam}', PengerjaanUjian::class)
        ->middleware('throttle:60,1')
        ->name('ujian.kerjakan');
});
