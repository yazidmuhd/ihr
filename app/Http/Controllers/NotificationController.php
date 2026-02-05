<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // GET /notifications/feed  (JSON)
    public function feed(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 403);

        $items = $user->notifications()
            ->orderByDesc('created_at')
            ->limit(15)
            ->get()
            ->map(function ($n) {
                return [
                    'id'         => $n->id,
                    'type'       => $n->type, // optional (nice for debugging)
                    'read_at'    => optional($n->read_at)->toIso8601String(),
                    'created_at' => optional($n->created_at)->toIso8601String(),
                    'data'       => $n->data ?? [],
                ];
            })
            ->values();

        // ✅ cheaper than loading unreadNotifications collection
        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'unread_count' => $unreadCount,
            'items'        => $items,
        ]);
    }

    // POST /notifications/{id}/read
    public function markRead(Request $request, string $id)
    {
        $user = $request->user();
        abort_unless($user, 403);

        $n = $user->notifications()->where('id', $id)->firstOrFail();

        if (is_null($n->read_at)) {
            $n->markAsRead();
        }

        return response()->json([
            'ok'          => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    // POST /notifications/read-all
    public function markAllRead(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 403);

        // ✅ mark all unread at DB level
        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'ok'           => true,
            'unread_count' => 0,
        ]);
    }
}
