<?php

namespace Laravel\Envoy\Tests;

use Exception;
use Laravel\Envoy\Compiler;
use Laravel\Envoy\TaskContainer;
use PHPUnit\Framework\TestCase;
use ReflectionObject;

class TaskContainerTest extends TestCase
{
    public function test_it_writes_the_compiled_file_to_a_process_specific_path()
    {
        $recipe = $this->recipe("@task('foo')\n    echo 'foo';\n@endtask");

        $path = $this->writeCompiledEnvoyFile($recipe);

        $this->assertStringContainsString('-'.getmypid().'.php', basename($path));

        unlink($path);
        unlink($recipe);
    }

    public function test_it_deletes_the_compiled_file_when_the_recipe_throws()
    {
        $recipe = $this->recipe("@setup\n    throw new Exception('boom');\n@endsetup");

        try {
            (new TaskContainer())->load($recipe, new Compiler());
        } catch (Exception $e) {
            ob_end_clean();
        }

        $this->assertSame([], glob(getcwd().'/Envoy'.md5_file($recipe).'*.php'));

        unlink($recipe);
    }

    private function recipe($contents)
    {
        $recipe = tempnam(sys_get_temp_dir(), 'Envoy');

        file_put_contents($recipe, $contents);

        return $recipe;
    }

    private function writeCompiledEnvoyFile($recipe)
    {
        $container = new TaskContainer();

        $r = new ReflectionObject($container);
        $method = $r->getMethod('writeCompiledEnvoyFile');
        $method->setAccessible(true);

        return $method->invoke($container, new Compiler(), $recipe, false);
    }
}
