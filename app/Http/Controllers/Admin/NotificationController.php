<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function index()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->latest()->paginate(20);
        return view('admin.notifications.index', compact('notifications'));
    }

    public function show($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();
        return view('admin.notifications.show', compact('notification'));
    }

    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->notifications()->unread()->update(['is_read' => true]);
        return redirect()->back()->with('success', 'تم تعليم جميع الإشعارات كمقروءة');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->delete();
        return redirect()->route('admin.notifications.index')->with('success', 'تم حذف الإشعار');
    }
}
