<?php

return [
    'allowed_emails' => array_filter(
        array_map('trim', explode(',', env('TOOLBOX_ALLOWED_EMAILS', '')))
    ),
];
