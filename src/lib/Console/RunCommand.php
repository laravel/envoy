<?php namespace Laravel\Envoy\Console;

use Laravel\Envoy\SSH;
use Laravel\Envoy\Task;
use Laravel\Envoy\Compiler;
use Laravel\Envoy\TaskContainer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends \Symfony\Component\Console\Command\Command {

	use Command;

	/**
	 * Configure the command options.
	 *
	 * @return void
	 */
	protected function configure()
	{
		$this->ignoreValidationErrors();

		$this->setName('run')
				->setDescription('Run an Envoy task')
				->addArgument('task', InputArgument::REQUIRED);
	}

	/**
	 * Execute the command.
	 *
	 * @return void
	 */
	protected function fire()
	{
		$container = $this->loadTaskContainer();

		if ($this->runTask($container->getTask($task = $this->argument('task'))) > 0)
		{
			return;
		}

		foreach ($container->getAfterCallbacks() as $callback)
		{
			call_user_func($callback, $task);
		}
	}

	/**
	 * Run the given task and return the exit code.
	 *
	 * @param  \Laravel\Envoy\Task  $task
	 * @return int
	 */
	protected function runTask(Task $task)
	{
		return (new SSH)->run($task, function($host, $line)
		{
			if (starts_with($line, 'Warning: Permanently added ')) return;

			$this->output->write('<comment>['.$host.']</comment>: '.trim($line).PHP_EOL);
		});
	}

	/**
	 * Load the task container instance with the Envoy file.
	 *
	 * @return \Laravel\Envoy\TaskContainer
	 */
	protected function loadTaskContainer()
	{
		with($container = new TaskContainer)->load(
			getcwd().'/Envoy.blade.php', new Compiler, $this->getOptions()
		);

		return $container;
	}

	/**
	 * Gather the dynamic options for the command.
	 *
	 * @return void
	 */
	protected function getOptions()
	{
		$options = [];

		// Here we will gather all of the command line options that have been specified with
		// the double hyphens in front of their name. We will make these available to the
		// Blade task file so they can be used in echo statemnets and other structures.
		foreach ($_SERVER['argv'] as $argument)
		{
			preg_match('/^\-\-(.*?)=(.*)$/', $argument, $match);

			if (count($match) > 0) $options[$match[1]] = $match[2];
		}

		return $options;
	}

}