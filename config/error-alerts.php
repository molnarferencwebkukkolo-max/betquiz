<?php

return [
    'enabled' => (bool) env('ERROR_ALERTS_ENABLED', false),
    'recipient' => env('ERROR_ALERT_EMAIL'),
    'cooldown_minutes' => (int) env('ERROR_ALERT_COOLDOWN_MINUTES', 15),
];
