<?php

namespace Laravel\Envoy\Tests;

use Laravel\Envoy\Console\RunCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

class RunCommandTest extends TestCase
{
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
