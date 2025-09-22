<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DeclarationController;

Route::post('/auth/request-otp', [AuthController::class, 'requestOtp']);
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile/is-complete', [ProfileController::class, 'isComplete']);
    Route::post('/profile/complete', [ProfileController::class, 'complete']);
    Route::post('/declarations', [DeclarationController::class, 'store']);
    Route::get('/declarations/search', [DeclarationController::class, 'search']);
});
