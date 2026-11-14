<?php

use App\Services\MetricsService;

// Ruta para que Prometheus haga scraping
$app->get('/api/metrics', function ($request, $response) {
    // No requiere autenticación (Prometheus necesita acceso público)
    $metricsService = new MetricsService($this->get('pdo'));
    $metrics = $metricsService->getPrometheusMetrics();
    
    return $response
        ->withHeader('Content-Type', 'text/plain; version=0.0.4')
        ->write($metrics);
});