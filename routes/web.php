<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PetaController;
use App\Http\Controllers\SekolahController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfilSekolahController;
use App\Http\Controllers\UserController;

// Publik
Route::get('/', [PetaController::class, 'index'])->name('peta.index');
Route::get('/data/sekolah', [PetaController::class, 'geojson'])
    ->name('peta.data');
Route::get('/profil-sekolah/{sekolah}',
    [ProfilSekolahController::class, 'show'])
    ->name('sekolah.profil.public');

// Tamu
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');
});

// Pengguna yang sudah login
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');
    Route::get('/dashboard', DashboardController::class)
        ->name('dashboard');

    // Ubah kata sandi
    Route::get('/password', [AuthController::class, 'showChangePassword'])
        ->name('password.edit');
    Route::put('/password', [AuthController::class, 'updatePassword'])
        ->name('password.update');

    // Dinas: daftar, tambah, dan hapus sekolah
    Route::middleware('role:dinas')->prefix('dinas')->name('dinas.')
        ->group(function () {
            Route::resource('sekolah', SekolahController::class)
                ->only(['index', 'create', 'store', 'destroy']);
        });

    // Admin: melihat dan mengubah data sekolah
    Route::middleware('role:admin')->prefix('admin')->name('admin.')
        ->group(function () {
            Route::get('sekolah', [SekolahController::class, 'index'])
                ->name('sekolah.index');
            Route::get('sekolah/{sekolah}/edit', [SekolahController::class, 'edit'])
                ->name('sekolah.edit');
            Route::put('sekolah/{sekolah}', [SekolahController::class, 'update'])
                ->name('sekolah.update');
        });

    // Admin: kelola pengguna
    Route::middleware('role:admin')->prefix('admin')->name('admin.')
        ->group(function () {
            Route::resource('users', UserController::class)->except('show');

            Route::patch('users/{user}/status', [UserController::class, 'toggleStatus'])
                ->name('users.toggle-status');
        });

    // Dinas dan Sekolah: ubah data (dibatasi Policy)
    Route::middleware('role:dinas,sekolah')->group(function () {
        Route::get('sekolah/{sekolah}/edit', [SekolahController::class, 'edit'])
            ->name('sekolah.edit');
        Route::put('sekolah/{sekolah}', [SekolahController::class, 'update'])
            ->name('sekolah.update');
        Route::get('sekolah/{sekolah}/profil',
            [ProfilSekolahController::class, 'edit'])->name('sekolah.profil.edit');
        Route::put('sekolah/{sekolah}/profil',
            [ProfilSekolahController::class, 'update'])->name('sekolah.profil.update');
    });
});