<?php

namespace Laravel\Envoy\Console;

use Illuminate\Support\Str;
use Laravel\Envoy\Compiler;
use Laravel\Envoy\ParallelSSH;
use Laravel\Envoy\SSH;
use Laravel\Envoy\Task;
use Laravel\Envoy\TaskContainer;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class RunCommand extends SymfonyCommand
{
    use Command;

    /**
     * Command line options that should not be gathered dynamically.
     *
     * @var array
     */
    protected $ignoreOptions = [
        '--continue',
        '--pretend',
        '--help',
        '--quiet',
        '--version',
        '--asci',
        '--no-asci',
        '--no-interactions',
        '--verbose',
    ];

    /**
     * The hosts that have already been assigned a color for output.
     *
     * @var array
     */
    protected $hostsWithColor = [];

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this->ignoreValidationErrors();

        $this->setName('run')
                ->setDescription('Run an Envoy task.')
                ->addArgument('task', InputArgument::REQUIRED)
                ->addOption('continue', null, InputOption::VALUE_NONE, 'Continue running even if a task fails')
                ->addOption('pretend', null, InputOption::VALUE_NONE, 'Dump Bash script for inspection')
                ->addOption('path', null, InputOption::VALUE_REQUIRED, 'The path to the Envoy.blade.php file')
                ->addOption('conf', null, InputOption::VALUE_REQUIRED, 'The name of the Envoy file', 'Envoy.blade.php');
    }

    /**
     * Execute the command.
     *
     * @return int
     */
    protected function fire()
    {
        $container = $this->loadTaskContainer();

        $exitCode = 0;

        foreach ($this->getTasks($container) as $task) {
            $thisCode = $this->runTask($container, $task);

            if (0 !== $thisCode) {
                $exitCode = $thisCode;
            }

            if ($thisCode > 0 && ! $this->input->getOption('continue')) {
                $this->output->writeln('[<fg=red>✗</>] <fg=red>This task did not complete successfully on one of your servers.</>');

                break;
            }
        }

        if (! $thisCode) {
            foreach ($container->getSuccessCallbacks() as $callback) {
                call_user_func($callback);
            }
        }

        foreach ($container->getFinishedCallbacks() as $callback) {
            call_user_func($callback, $exitCode);
        }

        return $exitCode;
    }

    /**
     * Get the tasks from the container based on user input.
     *
     * @param  \Laravel\Envoy\TaskContainer  $container
     * @return array
     */
    protected function getTasks($container)
    {
        $tasks = [$task = $this->argument('task')];

        if ($macro = $container->getMacro($task)) {
            $tasks = $macro;
        }

        return $tasks;
    }

    /**
     * Run the given task out of the container.
     *
     * @param  \Laravel\Envoy\TaskContainer  $container
     * @param  string  $task
     * @return null|int|void
     */
    protected function runTask($container, $task)
    {
        $macroOptions = $container->getMacroOptions($this->argument('task'));

        $confirm = $container->getTask($task, $macroOptions)->confirm;

        if ($confirm && ! $this->confirmTaskWithUser($task, $confirm)) {
            return;
        }

        foreach ($container->getBeforeCallbacks() as $callback) {
            call_user_func($callback, $task);
        }

        if (($exitCode = $this->runTaskOverSSH($container->getTask($task, $macroOptions))) > 0) {
            foreach ($container->getErrorCallbacks() as $callback) {
                call_user_func($callback, $task);
            }

            return $exitCode;
        }

        foreach ($container->getAfterCallbacks() as $callback) {
            call_user_func($callback, $task);
        }
    }

    /**
     * Run the given task and return the exit code.
     *
     * @param  \Laravel\Envoy\Task  $task
     * @return int
     */
    protected function runTaskOverSSH(Task $task)
    {
        // If the pretending option has been set, we'll simply dump the script out to the command
        // line so the developer can inspect it which is useful for just inspecting the script
        // before it is actually run against these servers. Allows checking for errors, etc.
        if ($this->pretending()) {
            echo $task->script.PHP_EOL;

            return 1;
        } else {
            return $this->passToRemoteProcessor($task);
        }
    }

    /**
     * Run the given task and return the exit code.
     *
     * @param  \Laravel\Envoy\Task  $task
     * @return int
     */
    protected function passToRemoteProcessor(Task $task)
    {
        return $this->getRemoteProcessor($task)->run($task, function ($type, $host, $line) {
            if (Str::startsWith($line, 'Warning: Permanently added ')) {
                return;
            }

            $this->displayOutput($type, $host, $line);
        });
    }

    /**
     * Display the given output line.
     *
     * @param  string  $type
     * @param  string  $host
     * @param  string  $line
     * @return void
     */
    protected function displayOutput($type, $host, $line)
    {
        $lines = array_filter(array_map('trim', explode("\n", $line)));

        $hostColor = $this->getHostColor($host);

        foreach ($lines as $line) {
            if ($type === Process::ERR) {
                $line = '<fg=red>'.$line.'</>';
            }

            $this->output->write($hostColor.': '.$line.PHP_EOL);
        }
    }

    /**
     * Load the task container instance with the Envoy file.
     *
     * @return \Laravel\Envoy\TaskContainer
     */
    protected function loadTaskContainer()
    {
        $path = $this->input->getOption('path', '');

        $file = $this->input->getOption('conf');
        $envoyFile = $path;

        if (! file_exists($envoyFile ?? '')
            && ! file_exists($envoyFile = getcwd().'/'.$file)
            && ! file_exists($envoyFile .= '.blade.php')
        ) {
            echo "{$file} not found.\n";

            exit(1);
        }

        with($container = new TaskContainer)->load(
            $envoyFile,
            new Compiler,
            array_merge($this->getOptions(), ['__task' => $this->argument('task')])
        );

        return $container;
    }

    /**
     * Return the hostname wrapped in a color tag.
     *
     * @param  string  $host
     * @return string
     */
    protected function getHostColor($host)
    {
        $colors = ['yellow', 'cyan', 'magenta', 'blue'];

        if (! in_array($host, $this->hostsWithColor)) {
            $this->hostsWithColor[] = $host;
        }

        $color = $colors[array_search($host, $this->hostsWithColor) % count($colors)];

        return "<fg={$color}>[{$host}]</>";
    }

    /**
     * Gather the dynamic options for the command.
     *
     * @return array
     */
    protected function getOptions()
    {
        $options = [];

        // Here we will gather all of the command line options that have been specified with
        // the double hyphens in front of their name. We will make these available to the
        // Blade task file so they can be used in echo statements and other structures.
        foreach ($_SERVER['argv'] as $argument) {
            if (! Str::startsWith($argument, '--') || in_array($argument, $this->ignoreOptions)) {
                continue;
            }

            $option = explode('=', substr($argument, 2), 2);

            if (count($option) == 1) {
                $option[1] = true;
            }

            $optionKey = $option[0];

            $options[Str::camel($optionKey)] = $option[1];
            $options[Str::snake($optionKey)] = $option[1];
        }

        return $options;
    }

    /**
     * Determine if the SSH command should be dumped.
     *
     * @return bool
     */
    protected function pretending()
    {
        return $this->input->getOption('pretend');
    }

    /**
     * Get the SSH processor for the task.
     *
     * @param  \Laravel\Envoy\Task  $task
     * @return \Laravel\Envoy\RemoteProcessor
     */
    protected function getRemoteProcessor(Task $task)
    {
        return $task->parallel ? new ParallelSSH : new SSH;
    }
}
