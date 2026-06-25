<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    public function markRead(string $id): RedirectResponse
    {
        auth()->user()->notifications()->findOrFail($id)->markAsRead();

        return back();
    }

    public function readAll(): RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back();
    }
}
