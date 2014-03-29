<?php namespace Laravel\Envoy;

use Closure;
use Symfony\Component\Process\Process;

abstract class RemoteProcessor {

	/**
	 * Run the given task over SSH.
	 *
	 * @param  \Laravel\Envoy\Task  $task
	 * @return void
	 */
	abstract public function run(Task $task, Closure $callback = null);

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
			'ssh -T '.$target.' << "EOF"
'.$script.'
EOF'
		);

		return [$target, $process->setTimeout(null)];
	}

	/**
	 * Gather the cumulative exit code for the processes.
	 *
	 * @param  array  $processes
	 * @return int
	 */
	protected function gatherExitCodes(array $processes)
	{
		$code = 0;

		foreach ($processes as $process)
		{
			$code = $code + $process->getExitCode();
		}

		return $code;
	}

}
