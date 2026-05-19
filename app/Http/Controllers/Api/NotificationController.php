<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * GET /api/notification
     * Notifikasi milik user yang sedang login.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // Tandai semua sebagai dibaca
        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'success'      => true,
            'unread_count' => $notifications->where('is_read', false)->count(),
            'data'         => $notifications->map(fn($n) => [
                'id'         => $n->id,
                'title'      => $n->title,
                'message'    => $n->message,
                'type'       => $n->type,
                'is_read'    => $n->is_read,
                'created_at' => $n->created_at->toISOString(),
            ]),
        ]);
    }

    /**
     * DELETE /api/notification
     * Hapus semua notifikasi milik user yang login.
     */
    public function destroy(Request $request): JsonResponse
    {
        $deleted = Notification::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => "{$deleted} notifikasi berhasil dihapus.",
        ]);
    }
}
