<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('dashboard.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
