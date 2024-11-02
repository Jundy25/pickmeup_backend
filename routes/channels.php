<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('dashboard', function ($user) {
    return true; // Add your logic to authorize the user here
});
Broadcast::channel('rides', function ($user) {
    return true; // Add your logic to authorize the user here
});

