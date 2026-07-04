<?php

namespace App\Support;

use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class WebPushService
{
    public static function sendToBranch(int $branchId, string $title, string $body, array $data = []): void
    {
        $vapidPublic  = config('webpush.vapid_public');
        $vapidPrivate = config('webpush.vapid_private');

        if (!$vapidPublic || !$vapidPrivate) return;

        // Get all users in branch via subscriptions joined through users
        $subscriptions = PushSubscription::whereHas('user', fn($q) => $q->where('branch_id', $branchId))
            ->get();

        if ($subscriptions->isEmpty()) return;

        $webPush = new WebPush([
            'VAPID' => [
                'subject'    => config('app.url'),
                'publicKey'  => $vapidPublic,
                'privateKey' => $vapidPrivate,
            ],
        ], ['SSL' => ['verify_peer' => config('webpush.verify_ssl', true)]]);

        $payload = json_encode([
            'title' => $title,
            'body'  => $body,
            'data'  => $data,
        ]);

        foreach ($subscriptions as $sub) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint'        => $sub->endpoint,
                    'keys'            => [
                        'p256dh' => $sub->public_key,
                        'auth'   => $sub->auth_token,
                    ],
                ]),
                $payload
            );
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                PushSubscription::where('endpoint', $report->getRequest()->getUri()->__toString())->delete();
            }
        }
    }
}
