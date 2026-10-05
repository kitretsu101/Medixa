<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| Live Queue Channel (Public)
|--------------------------------------------------------------------------
|
| The queue channel is intentionally public — any visitor can watch
| the live OPD serial number for a given doctor.  No authentication
| required, so we use a regular Channel (not PrivateChannel).
|
| The channel is registered here only for documentation; public channels
| don't require authorization callbacks.
|
*/
