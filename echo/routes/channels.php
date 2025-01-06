<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('private-event', function () {
    return true;
});

Broadcast::channel('channel-chat', function ($user) { return true; });
