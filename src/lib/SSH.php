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
		foreach ($task->hosts as $host)
		{
			$this->runOnHost($host, $task, $callback ?: function() {});
		}
	}

	/**
	 * Run the given script on the given host.
	 *
	 * @param  string  $host
	 * @param  \Laravel\Envoy\Task  $task
	 * @param  \Closure  $callback
	 * @return int
	 */
	protected function runOnHost($host, Task $task, Closure $callback)
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

		$process->setTimeout(null)->run(function($type, $line) use ($callback, $target)
		{
			$callback($target, $line);
		});

		return $process->getExitCode();
	}

}