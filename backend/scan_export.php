<?php

require __DIR__ . '/vendor/autoload.php';

use OpenApi\Generator;
use OpenApi\Analysis;
use OpenApi\Context;
use Psr\Log\NullLogger;

$source   = __DIR__ . '/app/Swagger';
$outFile  = __DIR__ . '/storage/api-docs/api-docs.json';

@mkdir(dirname($outFile), 0777, true);

$context  = new Context();
$analysis = new Analysis([], $context);

$openapi = (new Generator(new NullLogger()))
    ->generate([$source], $analysis, false);

// เขียน JSON ลงไฟล์
file_put_contents($outFile, $openapi->toJson(JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

echo "OK: wrote {$outFile}\n";