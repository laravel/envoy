<?php

namespace Laravel\Envoy\Tests;

use Symfony\Component\Process\Process;

trait InteractsWithEnvoyFiles
{
    /**
     * The temporary directory holding the Envoy fixtures.
     *
     * @var string
     */
    protected $envoyDirectory;

    /**
     * The working directory before the test was run.
     *
     * @var string
     */
    protected $originalWorkingDirectory;

    /**
     * Create a temporary directory and make it the working directory.
     *
     * @return void
     */
    protected function setUpEnvoyDirectory(): void
    {
        $this->originalWorkingDirectory = getcwd();

        $directory = sys_get_temp_dir().'/envoy-tests-'.bin2hex(random_bytes(8));

        mkdir($directory);

        $this->envoyDirectory = realpath($directory);

        chdir($this->envoyDirectory);
    }

    /**
     * Restore the working directory and remove the temporary directory.
     *
     * @return void
     */
    protected function tearDownEnvoyDirectory(): void
    {
        chdir($this->originalWorkingDirectory);

        $this->deleteDirectory($this->envoyDirectory);
    }

    /**
     * Write an Envoy file declaring a server, a task and a story named after the given prefix.
     *
     * @param  string  $path
     * @param  string  $name
     * @return string
     */
    protected function writeEnvoyFile(string $path, string $name): string
    {
        $file = $this->envoyDirectory.'/'.$path;

        if (! is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }

        file_put_contents($file, implode("\n", [
            "@servers(['{$name}-server' => '{$name}@example.com'])",
            '',
            "@task('{$name}-task', ['on' => '{$name}-server'])",
            "    echo {$name}",
            '@endtask',
            '',
            "@story('{$name}-story')",
            "    {$name}-task",
            '@endstory',
            '',
        ]));

        return $file;
    }

    /**
     * Run the Envoy binary inside the temporary directory.
     *
     * @param  array  $arguments
     * @return \Symfony\Component\Process\Process
     */
    protected function runEnvoyBinary(array $arguments): Process
    {
        $process = new Process(
            array_merge([PHP_BINARY, __DIR__.'/../bin/envoy'], $arguments),
            $this->envoyDirectory
        );

        $process->run();

        return $process;
    }

    /**
     * Recursively delete the given directory.
     *
     * @param  string  $directory
     * @return void
     */
    private function deleteDirectory(string $directory): void
    {
        foreach (array_diff(scandir($directory), ['.', '..']) as $item) {
            $path = $directory.'/'.$item;

            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
