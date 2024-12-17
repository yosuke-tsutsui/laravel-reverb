<?php

use App\Events\PublicEvent;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    PublicEvent::dispatch();
    return 'public';
});

Route::get('/public-event', function(){
    broadcast(new PublicEvent);
    return 'public';
});

Route::get('socket.io/public-event', function(){
    broadcast(new PublicEvent);
    return 'public';
});
