<?php

return [

    'X-Frame-Options' => 'DENY',

    'X-Content-Type-Options' => 'nosniff',

    'X-XSS-Protection' => '1; mode=block',

    'Referrer-Policy' => 'no-referrer-when-downgrade',

    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',

    'Content-Security-Policy' => "default-src 'self'; img-src 'self' https://trusted-cdn.com; script-src 'self' https://apis.google.com",

];
