<?php

namespace Amreljako\SecureHeaders;

use Illuminate\Support\ServiceProvider;

class SecureHeadersServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/secure-headers.php' => config_path('secure-headers.php'),
        ], 'config');
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/secure-headers.php',
            'secure-headers'
        );
    }
}
