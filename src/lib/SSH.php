<?php namespace Laravel\Envoy;

use Closure;
use Symfony\Component\Process\Process;

class SSH {

	use ConfigurationParser;

	/**
	 * Run the given task over SSH.
	 *
	 * @param  \Laravel\Envoy\Task  $task
	 * @return void
	 */
	public function run(Task $task, Closure $callback = null)
	{
		$processes = [];

		$callback = $callback ?: function() {};

		foreach ($task->hosts as $host)
		{
			$process = $this->getProcess($host, $task, $callback);

			$processes[$process[0]] = $process[1];
		}

		$this->startProcesses($processes);

		while($this->areRunning($processes))
		{
			$this->gatherOutput($processes, $callback);
		}

		$this->gatherOutput($processes);

		return $this->gatherExitCodes($processes);
	}

	/**
	 * Start all of the processes.
	 *
	 * @param  array  $processes
	 * @return void
	 */
	protected function startProcesses(array $processes)
	{
		foreach (processes as $process)
		{
			$process->start();
		}
	}

	/**
	 * Determine if any of the processes are running.
	 *
	 * @param  array  $processes
	 * @return bool
	 */
	protected function areRunning(array $processes)
	{
		foreach ($processes as $process)
		{
			if ($process->isRunning()) return true;
		}

		return false;
	}

	protected function gatherOutput(array $processes, Closure $callback)
	{
		foreach ($processes as $target => $process)
		{
			$output = $process->getIncrementalOutput();

			if ($output !== false)
			{
				$callback($target, $output);
			}
		}
	}

	protected function gatherExitCodes(array $processes)
	{
		$code = 0;

		foreach ($processes as $process)
		{
			$code = $code + $process->getExitCode();
		}

		return $code;
	}

	/**
	 * Run the given script on the given host.
	 *
	 * @param  string  $host
	 * @param  \Laravel\Envoy\Task  $task
	 * @return int
	 */
	protected function getProcess($host, Task $task)
	{
		$target = $this->getConfiguredServer($host) ?: $host;

		$script = 'set -e'.PHP_EOL.$task->script;

		// Here will run the SSH task on the server inline. We do not need to write the
		// script out to a file or anything. We will start the SSH process then pass
		// these lines of output back to the parent callback for display purposes.
		$process = new Process(
			'ssh '.$target.' \'bash -s\' << EOF
'.$script.'
EOF'
		);

		return [$target, $process->setTimeout(null)];
	}

}