<?php

return [
    // Productionben szükség esetén GOOGLE_ANALYTICS_ID környezeti változóval
    // felülírható, de a KwizzGo jelenlegi mérési azonosítója az alapérték.
    'measurement_id' => env('GOOGLE_ANALYTICS_ID', 'G-WG40CJBW03'),
];
