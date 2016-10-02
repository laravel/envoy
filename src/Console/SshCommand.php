<?php

namespace Laravel\Envoy\Console;

use Laravel\Envoy\Compiler;
use Laravel\Envoy\TaskContainer;
use Laravel\Envoy\ConfigurationParser;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SshCommand extends \Symfony\Component\Console\Command\Command
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
     * @return void
     */
    protected function fire()
    {
        $host = $this->getServer($container = $this->loadTaskContainer());

        passthru('ssh '.($this->getConfiguredServer($host) ?: $host));
    }

    /**
     * Get the server from the task container.
     *
     * @param  \Laravel\Envoy\TaskContainer  $container
     * @throws \InvalidArgumentException
     * @return string
     */
    protected function getServer(TaskContainer $container)
    {
        if ($this->argument('name')) {
            return $container->getServer($this->argument('name'));
        } elseif ($container->hasOneServer()) {
            return $container->getFirstServer();
        } else {
            throw new \InvalidArgumentException('Please provide a server name.');
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
