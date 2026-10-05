<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * In-app notifications for staff users.
 */
class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('notifications.index', ['notifications' => $request->user()->notifications()->paginate(20)]);
    }

    public function read(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        return is_string($url) && str_starts_with($url, url('/')) ? redirect()->to($url) : back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->done(back(), 'All notifications marked as read.');
    }
}
