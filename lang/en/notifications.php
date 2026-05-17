<?php

declare(strict_types=1);

return [
    'post_ready' => [
        'title' => 'Your post is ready',
        'body' => 'The AI just finished. Tap to review and publish.',
    ],
    'account_disconnected' => [
        'title' => ':platform account disconnected',
        'body' => ':account needs to be reconnected',
    ],
    'account_token_expired' => [
        'title' => ':platform account needs to be reconnected',
        'body' => ':account session expired — please reconnect to keep posting',
    ],
    'inbox_item_received' => [
        'title' => 'You have :kind on :platform',
        'body' => ':handle reached out via your inbox',
    ],
];
