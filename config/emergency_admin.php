<?php

return [
    // Vészhelyzeti hostadmin: a belépési jelszó csak bcrypt hashként kerül az ENV-be.
    'enabled' => (bool) env('EMERGENCY_ADMIN_ENABLED', false),
    'email' => env('EMERGENCY_ADMIN_EMAIL'),
    'password_hash' => env('EMERGENCY_ADMIN_PASSWORD_HASH'),
];
