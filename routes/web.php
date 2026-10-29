<?php

use App\Http\Controllers\SekolahController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('sekolah', SekolahController::class)->except('show');
});