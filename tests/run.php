<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require __DIR__ . '/Application/SystemSettings/UpdateSystemSettingsTest.php';

use Tests\Application\SystemSettings\UpdateSystemSettingsTest;

function assertSame(mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(sprintf(
            "Expected:\n%s\nActual:\n%s",
            var_export($expected, true),
            var_export($actual, true),
        ));
    }
}

/**
 * @param class-string<Throwable> $expected
 */
function assertThrows(string $expected, callable $callback): void
{
    try {
        $callback();
    } catch (Throwable $e) {
        if ($e instanceof $expected) {
            return;
        }
        throw $e;
    }

    throw new RuntimeException("Expected {$expected} to be thrown.");
}

$test = new UpdateSystemSettingsTest();
$methods = array_filter(
    get_class_methods($test),
    static fn (string $method): bool => str_starts_with($method, 'test'),
);

foreach ($methods as $method) {
    $test->{$method}();
    fwrite(STDOUT, "PASS {$method}\n");
}
