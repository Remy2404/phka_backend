<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing API Routes...\n\n";

// Test route registration
$router = app('router');
$routes = $router->getRoutes();

$apiRoutes = [];
foreach ($routes as $route) {
    if (str_starts_with($route->uri(), 'api/')) {
        $apiRoutes[] = [
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
            'name' => $route->getName() ?: 'unnamed',
            'middleware' => $route->middleware()
        ];
    }
}

echo "Found " . count($apiRoutes) . " API routes:\n\n";

foreach ($apiRoutes as $route) {
    echo sprintf("%-10s %-40s %s\n",
        $route['method'],
        $route['uri'],
        !empty($route['middleware']) ? '[' . implode(', ', $route['middleware']) . ']' : ''
    );
}

echo "\nAPI Routes test completed.\n";