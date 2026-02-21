<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConversionController;

Route::get('/', [ConversionController::class, 'index']);
Route::post('/conversions', [ConversionController::class, 'store'])->name('conversions.store');
Route::get('/conversions/{conversion}', [ConversionController::class, 'show'])->name('conversions.show');
Route::get('/conversions/{conversion}/download', [ConversionController::class, 'download'])->name('conversions.download');