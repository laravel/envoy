<?php

namespace Laravel\Envoy\Tests;

use closure;
use PHPUnit\Framework\TestCase;

class E2ETest extends TestCase
{
    public function fixtures(): array
    {
        $data = [];
        foreach (scandir(__DIR__.'/fixtures') as $file) {
            $fullPath = __DIR__.'/fixtures/'.$file;
            if (str_ends_with($file, '.blade.php')) {
                $spec = __DIR__.'/fixtures/'.str_replace('blade.php', 'spec.php', $file);
                if (is_file($spec)) {
                    $specs = require $spec;
                    foreach ($specs as $task => $func) {
                        $data[] = [$file, $task, $func];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @dataProvider fixtures
     */
    public function testFixtures(string $file, string $task, closure $func)
    {
        exec(__DIR__."/../bin/envoy run --path tests/fixtures/{$file} {$task}", $output, $code);
        $func->call($this, $output, $code);
    }
}
