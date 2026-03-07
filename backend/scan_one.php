<?php

require __DIR__ . '/vendor/autoload.php';

use OpenApi\Generator;
use OpenApi\Analysis;
use OpenApi\Context;
use Psr\Log\NullLogger;

$validate = false;

// โฟลเดอร์ที่คุณให้ L5 สแกนอยู่ตอนนี้คือ app/Swagger
$source = __DIR__ . '/app/Swagger';

$context = new Context();
$analysis = new Analysis([], $context);

$openapi = (new Generator(new NullLogger()))
    ->generate([$source], $analysis, $validate);

echo "=== scan_one.php ===\n";
echo "source={$source}\n";
echo "has_info=" . (isset($openapi->info) ? "yes" : "no") . "\n";

// $openapi->paths บางทีอาจไม่ใช่ array ตรง ๆ ขึ้นกับ internal model
$pathsCount = 0;
if (isset($openapi->paths) && is_countable($openapi->paths)) {
    $pathsCount = count($openapi->paths);
}

echo "paths_count={$pathsCount}\n";
echo substr($openapi->toJson(), 0, 500) . "\n";