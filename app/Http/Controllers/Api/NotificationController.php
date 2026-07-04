<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrderNotification;
use App\Models\PushSubscription;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $notifications = OrderNotification::where('branch_id', $user->branch_id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json($notifications);
    }

    public function markRead(Request $request)
    {
        $user = $request->user();
        $id   = $request->input('id'); // null = mark all

        $query = OrderNotification::where('branch_id', $user->branch_id)
            ->whereNull('read_at');

        if ($id) {
            $query->where('id', $id);
        }

        $query->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    // ── Web Push subscriptions ────────────────────────────────────────────────

    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'endpoint'   => 'required|string|max:500',
            'public_key' => 'required|string',
            'auth_token' => 'required|string',
        ]);

        PushSubscription::updateOrCreate(
            ['user_id' => $request->user()->id, 'endpoint' => $data['endpoint']],
            ['public_key' => $data['public_key'], 'auth_token' => $data['auth_token']]
        );

        return response()->json(['ok' => true]);
    }

    public function unsubscribe(Request $request)
    {
        $data = $request->validate(['endpoint' => 'required|string']);

        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint', $data['endpoint'])
            ->delete();

        return response()->json(['ok' => true]);
    }
}
