<?php

use App\Http\Controllers\ManufacturerController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// Search Route
Route::get('/search', [SearchController::class, '__invoke'])->name('search');

// Home Route - Display Manufacturers
Route::get('/', [ManufacturerController::class, 'index'])->name('manufacturers.index');
Route::get('/{manufacturer}', [ManufacturerController::class, 'show'])->name('manufacturers.show');
