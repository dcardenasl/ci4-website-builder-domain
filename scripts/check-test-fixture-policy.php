<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$allowedSeederDirectory = $root . '/tests/Integration/Database/Seeds/';
$violations = [];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/tests'));
foreach ($iterator as $file) {
    if (! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $contents = (string) file_get_contents($path);
    if (str_starts_with($path, $allowedSeederDirectory)) {
        continue;
    }

    if (preg_match('/(?:Database::seeder|Seeder::class|->call\s*\(.*Seeder)/', $contents) === 1) {
        $violations[] = $path . ': general tests must not execute database seeders';
    }
}

if ($violations !== []) {
    fwrite(STDERR, implode(PHP_EOL, $violations) . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "Fixture policy passed: general tests are isolated from seeder execution.\n");
