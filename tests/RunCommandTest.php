<?php

namespace Laravel\Envoy\Tests;

use Laravel\Envoy\Console\RunCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

class RunCommandTest extends TestCase
{
    use InteractsWithEnvoyFiles;

    /**
     * @dataProvider optionOrders
     */
    public function test_native_and_dynamic_options_are_parsed_in_any_order(array $arguments): void
    {
        $this->runCommand($arguments, function ($input) {
            $this->assertSame('example-task', $input->getArgument('task'));
            $this->assertTrue($input->getOption('pretend'));
            $this->assertTrue($input->getOption('continue'));
            $this->assertSame('recipes', $input->getOption('path'));
            $this->assertSame('Deploy.blade.php', $input->getOption('conf'));
            $this->assertSame('check', $input->getOption('mode'));
        });
    }

    public static function optionOrders(): array
    {
        return [
            'dynamic before native' => [[
                'example-task', '--mode=check', '--pretend', '--continue',
                '--path=recipes', '--conf=Deploy.blade.php',
            ]],
            'dynamic between native' => [[
                'example-task', '--pretend', '--mode=check', '--continue',
                '--path=recipes', '--conf=Deploy.blade.php',
            ]],
            'dynamic after native' => [[
                'example-task', '--pretend', '--continue', '--path=recipes',
                '--conf=Deploy.blade.php', '--mode=check',
            ]],
        ];
    }

    public function test_boolean_and_value_dynamic_options_are_parsed(): void
    {
        $this->runCommand(['example-task', '--force', '--mode=check'], function ($input) {
            $this->assertTrue($input->getOption('force'));
            $this->assertSame('check', $input->getOption('mode'));
        });
    }

    public function test_absent_native_options_keep_their_defaults(): void
    {
        $this->runCommand(['example-task', '--mode=check'], function ($input) {
            $this->assertFalse($input->getOption('pretend'));
            $this->assertFalse($input->getOption('continue'));
            $this->assertNull($input->getOption('path'));
            $this->assertSame('Envoy.blade.php', $input->getOption('conf'));
        });
    }

    /**
     * @dataProvider invalidNativeOptions
     */
    public function test_invalid_native_option_values_are_rejected(array $arguments, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $this->runCommand($arguments, function () {
        });
    }

    public static function invalidNativeOptions(): array
    {
        return [
            'missing path value' => [['example-task', '--mode=check', '--path'], 'The "--path" option requires a value'],
            'missing conf value' => [['example-task', '--mode=check', '--conf'], 'The "--conf" option requires a value'],
            'pretend value' => [['example-task', '--mode=check', '--pretend=yes'], 'The "--pretend" option does not accept a value'],
            'continue value' => [['example-task', '--mode=check', '--continue=yes'], 'The "--continue" option does not accept a value'],
            'excess argument' => [['example-task', '--mode=check', 'unexpected'], 'Too many arguments'],
        ];
    }

    /**
     * @dataProvider applicationOptions
     */
    public function test_application_options_are_not_added_as_dynamic_options(string $option): void
    {
        $_SERVER['argv'] = ['envoy', 'run', 'example-task', '--mode=check', $option];

        $command = new TestRunCommand(function ($input) {
            $this->assertSame('check', $input->getOption('mode'));
        });

        $command->setApplication(new Application);

        $command->run(new ArgvInput($_SERVER['argv']), new BufferedOutput);
    }

    public static function applicationOptions(): array
    {
        return [
            'help' => ['--help'],
            'quiet' => ['--quiet'],
            'verbose' => ['--verbose'],
            'ansi' => ['--ansi'],
            'no-ansi' => ['--no-ansi'],
            'no-interaction' => ['--no-interaction'],
        ];
    }

    public function test_default_envoy_file_is_loaded(): void
    {
        $this->setUpEnvoyDirectory();

        try {
            $this->writeEnvoyFile('Envoy.blade.php', 'default');

            $command = $this->runResolvingCommand(['default-task']);

            $this->assertSame($this->envoyDirectory.'/Envoy.blade.php', $command->resolvedFile);
            $this->assertArrayHasKey('default-task', $command->container->getTasks());
        } finally {
            $this->tearDownEnvoyDirectory();
        }
    }

    /**
     * @dataProvider envoyFileOptions
     */
    public function test_envoy_file_options_are_resolved(array $arguments, string $expectedFile, string $expectedTask): void
    {
        $this->setUpEnvoyDirectory();

        try {
            $this->writeEnvoyFile('Envoy.blade.php', 'default');
            $this->writeEnvoyFile('Other.blade.php', 'other');
            $this->writeEnvoyFile('recipes/Deploy.blade.php', 'recipe');

            $command = $this->runResolvingCommand(array_merge([$expectedTask], $arguments));

            $this->assertSame(
                str_replace('{dir}', $this->envoyDirectory, $expectedFile),
                $command->resolvedFile
            );
            $this->assertArrayHasKey($expectedTask, $command->container->getTasks());
        } finally {
            $this->tearDownEnvoyDirectory();
        }
    }

    public static function envoyFileOptions(): array
    {
        return [
            'conf with extension' => [['--conf=Other.blade.php'], '{dir}/Other.blade.php', 'other-task'],
            'conf without extension' => [['--conf=Other'], '{dir}/Other.blade.php', 'other-task'],
            'relative path' => [['--path=recipes/Deploy.blade.php'], 'recipes/Deploy.blade.php', 'recipe-task'],
            'path takes precedence over conf' => [['--path=recipes/Deploy.blade.php', '--conf=Other.blade.php'], 'recipes/Deploy.blade.php', 'recipe-task'],
            'missing path falls back to conf' => [['--path=missing.blade.php', '--conf=Other.blade.php'], '{dir}/Other.blade.php', 'other-task'],
        ];
    }

    public function test_dynamic_options_are_passed_to_the_envoy_file(): void
    {
        $this->setUpEnvoyDirectory();

        try {
            file_put_contents($this->envoyDirectory.'/Envoy.blade.php', implode("\n", [
                "@servers(['web' => 'localhost'])",
                '@setup',
                "    file_put_contents(__DIR__.'/options.txt', \$branch.','.\$__task);",
                '@endsetup',
                "@task('deploy', ['on' => 'web'])",
                '    echo hello',
                '@endtask',
                '',
            ]));

            $command = $this->runResolvingCommand(['deploy', '--branch=main']);

            $this->assertSame('main,deploy', file_get_contents($this->envoyDirectory.'/options.txt'));
            $this->assertArrayHasKey('deploy', $command->container->getTasks());
        } finally {
            $this->tearDownEnvoyDirectory();
        }
    }

    public function test_missing_envoy_file_exits_with_an_error(): void
    {
        $this->setUpEnvoyDirectory();

        try {
            $process = $this->runEnvoyBinary(['run', 'deploy', '--conf=Missing.blade.php']);

            $this->assertSame(1, $process->getExitCode());
            $this->assertSame("Missing.blade.php not found.\n", $process->getOutput());
        } finally {
            $this->tearDownEnvoyDirectory();
        }
    }

    private function runResolvingCommand(array $arguments): ResolvingRunCommand
    {
        $_SERVER['argv'] = array_merge(['envoy', 'run'], $arguments);

        $command = new ResolvingRunCommand;

        $command->run(new ArgvInput(array_merge(['envoy'], $arguments)), new BufferedOutput);

        return $command;
    }

    private function runCommand(array $arguments, callable $callback): void
    {
        $_SERVER['argv'] = array_merge(['envoy', 'run'], $arguments);

        $command = new TestRunCommand($callback);
        $input = new ArgvInput(array_merge(['envoy'], $arguments));

        $command->run($input, new BufferedOutput);
    }
}

class TestRunCommand extends RunCommand
{
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;

        parent::__construct();
    }

    protected function fire()
    {
        ($this->callback)($this->input);

        return 0;
    }
}

class ResolvingRunCommand extends RunCommand
{
    public $resolvedFile;

    public $container;

    protected function fire()
    {
        $this->resolvedFile = $this->resolveEnvoyFile();

        $this->container = $this->loadTaskContainer();

        return 0;
    }
}
