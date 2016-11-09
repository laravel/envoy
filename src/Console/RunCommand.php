<?php

namespace Laravel\Envoy\Console;

use Laravel\Envoy\SSH;
use Laravel\Envoy\Task;
use Laravel\Envoy\Compiler;
use Laravel\Envoy\ParallelSSH;
use Laravel\Envoy\TaskContainer;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class RunCommand extends \Symfony\Component\Console\Command\Command
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
                ->addOption('pretend', null, InputOption::VALUE_NONE, 'Dump Bash script for inspection');
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

        foreach ($container->getFinishedCallbacks() as $callback) {
            call_user_func($callback);
        }

        return $exitCode;
    }

    /**
     * Get the tasks from the container based on user input.
     *
     * @param  \Laravel\Envoy\TaskContainer  $container
     * @return void
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
            if (starts_with($line, 'Warning: Permanently added ')) {
                return;
            }

            $this->displayOutput($type, $host, $line);
        });
    }

    /**
     * Display the given output line.
     *
     * @param  int  $type
     * @param  string  $host
     * @param  string  $line
     * @return void
     */
    protected function displayOutput($type, $host, $line)
    {
        $lines = explode("\n", $line);

        foreach ($lines as $line) {
            if (strlen(trim($line)) === 0) {
                continue;
            }

            if ($type == Process::OUT) {
                $this->output->write('<comment>['.$host.']</comment>: '.trim($line).PHP_EOL);
            } else {
                $this->output->write('<comment>['.$host.']</comment>: <fg=red>'.trim($line).'</>'.PHP_EOL);
            }
        }
    }

    /**
     * Load the task container instance with the Envoy file.
     *
     * @return \Laravel\Envoy\TaskContainer
     */
    protected function loadTaskContainer()
    {
        if (! file_exists($envoyFile = getcwd().'/Envoy.blade.php')) {
            echo "Envoy.blade.php not found.\n";

            exit(1);
        }

        with($container = new TaskContainer)->load(
            $envoyFile, new Compiler, $this->getOptions()
        );

        return $container;
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
            if (! starts_with($argument, '--') || in_array($argument, $this->ignoreOptions)) {
                continue;
            }

            $option = explode('=', substr($argument, 2));

            if (count($option) == 1) {
                $option[1] = true;
            }

            $options[$option[0]] = $option[1];
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
