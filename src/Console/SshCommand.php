<?php

namespace Laravel\Envoy\Console;

use InvalidArgumentException;
use Laravel\Envoy\Compiler;
use Laravel\Envoy\ConfigurationParser;
use Laravel\Envoy\TaskContainer;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SshCommand extends SymfonyCommand
{
    use Command, ConfigurationParser;

    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('ssh')
            ->setDescription('Connect to an Envoy server.')
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the server.')
            ->addOption('user', null, InputOption::VALUE_OPTIONAL, 'The name of the user.');
    }

    /**
     * Execute the command.
     *
     * @return int
     */
    protected function fire()
    {
        $host = $this->getServer($container = $this->loadTaskContainer());

        passthru('ssh '.($this->getConfiguredServer($host) ?: $host));

        return 0;
    }

    /**
     * Get the server from the task container.
     *
     * @param  \Laravel\Envoy\TaskContainer  $container
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getServer(TaskContainer $container)
    {
        if ($this->argument('name')) {
            return $container->getServer($this->argument('name'));
        } elseif ($container->hasOneServer()) {
            return $container->getFirstServer();
        } else {
            throw new InvalidArgumentException('Please provide a server name.');
        }
    }

    /**
     * Load the task container instance with the Envoy file.
     *
     * @return \Laravel\Envoy\TaskContainer
     */
    protected function loadTaskContainer()
    {
        with($container = new TaskContainer)->loadServers(
            getcwd().'/Envoy.blade.php', new Compiler, []
        );

        return $container;
    }
}
