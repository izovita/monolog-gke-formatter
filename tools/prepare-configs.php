<?php

declare(strict_types=1);

use Nette\Neon\Neon;

require_once __DIR__ . '/../vendor/autoload.php';

const MONOLOG_VERSION_21 = '^2.1';
const MONOLOG_VERSION_30 = '^3.0';

$monologVersion = class_exists('Monolog\LogRecord') ? MONOLOG_VERSION_30 : MONOLOG_VERSION_21;

if (MONOLOG_VERSION_30 === $monologVersion) {
    $skipPath = __DIR__ . '/../src/GkeFormatter.php';
} else {
    $skipPath = __DIR__ . '/../src/GkeFormatterMonolog30.php.php';
}

$neonFile = __DIR__ . '/../phpstan.neon';

$neonData = Neon::decodeFile($neonFile);
$neonData['parameters']['excludePaths'] = [
    realpath($skipPath),
];

file_put_contents($neonFile, Neon::encode($neonData, true));
