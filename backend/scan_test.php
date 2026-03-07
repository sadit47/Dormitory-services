<?php

require 'vendor/autoload.php';

use OpenApi\Generator;

$generator = new Generator();
$openapi = $generator->generate([__DIR__ . '/app/Http/Controllers']);

echo "=== Swagger Scan Result ===\n";
echo "OpenAPI version: " . ($openapi->openapi ?? 'N/A') . "\n";

$paths = $openapi->paths ?? null;

// swagger-php v4: $openapi->paths เป็น object (PathItems)
// เราแปลงเป็น array แบบปลอดภัย
$pathKeys = [];
if (is_object($paths)) {
    // PathItems implements IteratorAggregate
    foreach ($paths as $k => $v) {
        $pathKeys[] = $k;
    }
} elseif (is_array($paths)) {
    $pathKeys = array_keys($paths);
}

echo "Path count: " . count($pathKeys) . "\n";
if ($pathKeys) {
    echo "Detected paths:\n";
    foreach ($pathKeys as $p) echo " - $p\n";
}