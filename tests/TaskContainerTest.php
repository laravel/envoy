<?php

namespace Laravel\Envoy\Tests;

use Laravel\Envoy\Compiler;
use Laravel\Envoy\TaskContainer;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class TaskContainerTest extends TestCase
{
    public function test_it_writes_the_compiled_file_to_a_process_specific_path()
    {
        $recipe = tempnam(sys_get_temp_dir(), 'Envoy');
        file_put_contents($recipe, "@task('foo')\n    echo 'foo';\n@endtask");

        $path = $this->writeCompiledEnvoyFile($recipe);

        unlink($recipe);
        unlink($path);

        $this->assertStringContainsString('-'.getmypid().'.php', basename($path));
    }

    private function writeCompiledEnvoyFile($recipe)
    {
        $container = new TaskContainer;

        $r = new ReflectionObject($container);
        $method = $r->getMethod('writeCompiledEnvoyFile');
        $method->setAccessible(true);

        return $method->invoke($container, new Compiler, $recipe, false);
    }
}
