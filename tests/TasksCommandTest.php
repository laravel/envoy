<?php

namespace Laravel\Envoy\Tests;

use Laravel\Envoy\Console\TasksCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class TasksCommandTest extends TestCase
{
    use InteractsWithEnvoyFiles;

    protected function setUp(): void
    {
        $this->setUpEnvoyDirectory();
    }

    protected function tearDown(): void
    {
        $this->tearDownEnvoyDirectory();
    }

    public function test_default_envoy_file_is_listed(): void
    {
        $this->writeEnvoyFile('Envoy.blade.php', 'default');

        $output = $this->listTasks([]);

        $this->assertStringContainsString('default-task', $output);
        $this->assertStringContainsString('default-story', $output);
    }

    public function test_conf_option_selects_the_envoy_file(): void
    {
        $this->writeEnvoyFile('Envoy.blade.php', 'default');
        $this->writeEnvoyFile('Other.blade.php', 'other');

        $output = $this->listTasks(['--conf' => 'Other.blade.php']);

        $this->assertStringContainsString('other-task', $output);
        $this->assertStringContainsString('other-story', $output);
        $this->assertStringNotContainsString('default-task', $output);
    }

    public function test_conf_option_without_extension_appends_blade_extension(): void
    {
        $this->writeEnvoyFile('Envoy.blade.php', 'default');
        $this->writeEnvoyFile('Other.blade.php', 'other');

        $output = $this->listTasks(['--conf' => 'Other']);

        $this->assertStringContainsString('other-task', $output);
        $this->assertStringNotContainsString('default-task', $output);
    }

    public function test_path_option_selects_the_envoy_file(): void
    {
        $this->writeEnvoyFile('Envoy.blade.php', 'default');
        $this->writeEnvoyFile('recipes/Deploy.blade.php', 'recipe');

        $output = $this->listTasks(['--path' => 'recipes/Deploy.blade.php']);

        $this->assertStringContainsString('recipe-task', $output);
        $this->assertStringNotContainsString('default-task', $output);
    }

    public function test_absolute_path_option_selects_the_envoy_file(): void
    {
        $file = $this->writeEnvoyFile('recipes/Deploy.blade.php', 'recipe');

        $output = $this->listTasks(['--path' => $file]);

        $this->assertStringContainsString('recipe-task', $output);
    }

    public function test_missing_envoy_file_exits_with_an_error(): void
    {
        $process = $this->runEnvoyBinary(['tasks']);

        $this->assertSame(1, $process->getExitCode());
        $this->assertSame("Envoy.blade.php not found.\n", $process->getOutput());
    }

    public function test_missing_conf_file_exits_with_an_error(): void
    {
        $this->writeEnvoyFile('Envoy.blade.php', 'default');

        $process = $this->runEnvoyBinary(['tasks', '--conf=Missing.blade.php']);

        $this->assertSame(1, $process->getExitCode());
        $this->assertSame("Missing.blade.php not found.\n", $process->getOutput());
    }

    private function listTasks(array $options): string
    {
        $tester = new CommandTester(new TasksCommand);

        $this->assertSame(0, $tester->execute($options));

        return $tester->getDisplay();
    }
}
