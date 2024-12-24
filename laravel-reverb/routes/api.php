<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Events\PublicEvent;
use App\Http\Controllers\ChatController;

Route::get('/chat', [ChatController::class, 'index']);
Route::post('/chat', [ChatController::class, 'store']);
