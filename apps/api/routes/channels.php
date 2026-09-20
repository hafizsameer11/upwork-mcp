<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('dashboard', function ($user) {
    return $user !== null;
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
