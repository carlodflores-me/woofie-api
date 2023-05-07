<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Retrieve all unread notifications for the authenticated user
        $notifications = Notification::where('notifiable_id', $user->id)
            ->where('notifiable_type', get_class($user))
            ->where('read_at', null)
            ->orderBy('created_at', 'desc')
            ->get();

        return NotificationResource::collection($notifications);
    }

    public function markAsRead(Request $request, Notification $notification)
    {
        $user = $request->user();

        // Check if the notification belongs to the authenticated user
        if ($notification->notifiable_id != $user->id || $notification->notifiable_type != get_class($user)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Mark the notification as read
        $notification->read_at = now();
        $notification->save();

        return new NotificationResource($notification);
    }
}