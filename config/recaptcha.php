<?php

return [
    // Kulcs nélkül helyben és tesztkörnyezetben nem blokkoljuk az auth-folyamatot.
    'enabled' => (bool) env('RECAPTCHA_ENABLED', false),
    'site_key' => env('RECAPTCHA_SITE_KEY'),
    'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    // A v3 pontszám 0.0–1.0 között mozog; az 0.5 jó induló kompromisszum.
    'minimum_score' => (float) env('RECAPTCHA_MINIMUM_SCORE', 0.5),
    'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
];
