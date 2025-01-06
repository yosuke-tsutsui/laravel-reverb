<?php

use App\Http\Controllers\ChatController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/apps/{appId}/events', [ChatController::class, 'events'])
    ->withoutMiddleware(ValidateCsrfToken::class);
