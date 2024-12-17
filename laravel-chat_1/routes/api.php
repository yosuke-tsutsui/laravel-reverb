<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Events\PublicEvent;
use App\Http\Controllers\ChatController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/test', function() {
    return response()->json([
        'message' => 'API test.',
    ]);
});

Route::get('/chat', [ChatController::class, 'index']);

//Route::get('/private-event', function(){
//    broadcast(new \App\Events\PrivateEvent());
//    return 'private';
//
//});
