<?php

namespace Laravel\Envoy\Console;

use Laravel\Envoy\Compiler;
use Laravel\Envoy\TaskContainer;

class TasksCommand extends \Symfony\Component\Console\Command\Command
{
    use Command;

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('tasks')
                ->setDescription('Lists all Envoy tasks and macros.');
    }

    /**
     * Execute the command.
     *
     * @return void
     */
    protected function fire()
    {
        $container = $this->loadTaskContainer();

        $this->listTasks($container);

        $this->output->writeln('');

        $this->listMacros($container);
    }

    /**
     * List the tasks from the container.
     *
     * @param  \Laravel\Envoy\TaskContainer  $container
     * @return void
     */
    protected function listTasks($container)
    {
        $this->output->writeln('<comment>Available tasks:</comment>');

        foreach (array_keys($container->getTasks()) as $task) {
            $this->output->writeln("  <info>{$task}</info>");
        }
    }

    /**
     * List the macros from the container.
     *
     * @param  \Laravel\Envoy\TaskContainer  $container
     * @return void
     */
    protected function listMacros($container)
    {
        $this->output->writeln('<comment>Available stories:</comment>');

        foreach (array_keys($container->getMacros()) as $macro) {
            $this->output->writeln("  <info>{$macro}</info>");
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

        with($container = new TaskContainer)->load($envoyFile, new Compiler);

        return $container;
    }
}
