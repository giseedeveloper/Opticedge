<?php

namespace App\Http\Controllers;

use App\Support\NotificationRoutes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationWebController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));

        $paginator = $user->notifications()->latest()->paginate($perPage);

        return response()->json([
            'data' => collect($paginator->items())
                ->map(fn (DatabaseNotification $n) => $this->serialize($n, $user))
                ->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        $count = Auth::user()->unreadNotifications()->count();

        return response()->json(['data' => ['unread_count' => $count]]);
    }

    public function markRead(string $id): JsonResponse
    {
        $user = Auth::user();
        $notification = $user->notifications()->where('id', $id)->first();
        if (! $notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return response()->json([
            'message' => 'Notification marked as read.',
            'data' => $this->serialize($notification->fresh(), $user),
        ]);
    }

    public function markAllRead(): JsonResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(DatabaseNotification $notification, $user): array
    {
        $data = is_array($notification->data) ? $notification->data : [];
        $type = (string) ($data['type'] ?? class_basename($notification->type));

        return [
            'id' => $notification->id,
            'type' => $type,
            'title' => $data['title'] ?? '',
            'body' => $data['body'] ?? '',
            'route' => $data['route'] ?? null,
            'web_url' => $data['web_url'] ?? NotificationRoutes::webForUser($user, $type, $data),
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at?->toISOString(),
        ];
    }
}
