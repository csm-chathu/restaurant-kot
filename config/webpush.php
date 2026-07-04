<?php

return [
    'vapid_public'  => env('VAPID_PUBLIC_KEY'),
    'vapid_private' => env('VAPID_PRIVATE_KEY'),
    'verify_ssl'    => env('PUSHER_VERIFY_SSL', true), // reuse same flag
];
