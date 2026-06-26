<?php

return [
    // Days before expiration when reminder notifications should be sent
    'reminder_days' => [30, 14, 7, 1],

    // Roles that should receive expiration reminders in addition to the submitter
    'notify_roles' => [
        'authorized_personnel',
        'coordinator',
        'administrative_aid',
        'president',
    ],

    // When true, authorization checks only use uploaded_by_id and ignore legacy uploaded_by name.
    'strict_uploader_identity' => env('AGREEMENTS_STRICT_UPLOADER_IDENTITY', false),
];
