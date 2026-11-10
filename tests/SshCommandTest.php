<?php

namespace Laravel\Envoy\Tests;

use Laravel\Envoy\Console\SshCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SshCommandTest extends TestCase
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

    public function test_default_envoy_file_servers_are_loaded(): void
    {
        $this->writeEnvoyFile('Envoy.blade.php', 'default');

        $command = $this->runSsh([]);

        $this->assertSame($this->envoyDirectory.'/Envoy.blade.php', $command->resolvedFile);
        $this->assertSame('default@example.com', $command->host);
    }

    public function test_conf_option_selects_the_envoy_file(): void
    {
        $this->writeEnvoyFile('Envoy.blade.php', 'default');
        $this->writeEnvoyFile('Other.blade.php', 'other');

        $command = $this->runSsh(['--conf' => 'Other.blade.php']);

        $this->assertSame($this->envoyDirectory.'/Other.blade.php', $command->resolvedFile);
        $this->assertSame('other@example.com', $command->host);
    }

    public function test_conf_option_without_extension_appends_blade_extension(): void
    {
        $this->writeEnvoyFile('Other.blade.php', 'other');

        $command = $this->runSsh(['--conf' => 'Other']);

        $this->assertSame($this->envoyDirectory.'/Other.blade.php', $command->resolvedFile);
        $this->assertSame('other@example.com', $command->host);
    }

    public function test_path_option_selects_the_envoy_file(): void
    {
        $this->writeEnvoyFile('Envoy.blade.php', 'default');
        $this->writeEnvoyFile('recipes/Deploy.blade.php', 'recipe');

        $command = $this->runSsh(['--path' => 'recipes/Deploy.blade.php']);

        $this->assertSame('recipes/Deploy.blade.php', $command->resolvedFile);
        $this->assertSame('recipe@example.com', $command->host);
    }

    public function test_named_server_is_resolved_from_the_selected_file(): void
    {
        $this->writeEnvoyFile('Other.blade.php', 'other');

        $command = $this->runSsh(['name' => 'other-server', '--conf' => 'Other.blade.php']);

        $this->assertSame('other@example.com', $command->host);
    }

    public function test_missing_envoy_file_exits_with_an_error(): void
    {
        $process = $this->runEnvoyBinary(['ssh']);

        $this->assertSame(1, $process->getExitCode());
        $this->assertSame("Envoy.blade.php not found.\n", $process->getOutput());
    }

    public function test_missing_conf_file_exits_with_an_error(): void
    {
        $this->writeEnvoyFile('Envoy.blade.php', 'default');

        $process = $this->runEnvoyBinary(['ssh', '--conf=Missing.blade.php']);

        $this->assertSame(1, $process->getExitCode());
        $this->assertSame("Missing.blade.php not found.\n", $process->getOutput());
    }

    private function runSsh(array $input): TestSshCommand
    {
        $tester = new CommandTester($command = new TestSshCommand);

        $this->assertSame(0, $tester->execute($input));

        return $command;
    }
}

class TestSshCommand extends SshCommand
{
    public $resolvedFile;

    public $host;

    protected function fire()
    {
        $this->resolvedFile = $this->resolveEnvoyFile();

        $this->host = $this->getServer($this->loadTaskContainer());

        return 0;
    }
}
