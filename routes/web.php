<?php

use App\Http\Controllers\Api\DoctorController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::prefix('api')->group(function (): void {
    Route::apiResource('doctors', DoctorController::class)->only('index');
});
