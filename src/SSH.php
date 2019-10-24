<?php

namespace Laravel\Envoy;

use Closure;

class SSH extends RemoteProcessor
{
    use ConfigurationParser;

    /**
     * Run the given task over SSH.
     *
     * @param  \Laravel\Envoy\Task  $task
     * @param  \Closure|null  $callback
     * @return int
     */
    public function run(Task $task, Closure $callback = null)
    {
        $processes = [];

        $callback = $callback ?: function () {
        };

        // Here we will gather all the process instances by host. We will build them in
        // an array so we can easily loop through them then start them up. We'll key
        // the array by the target name and set the value as the process instance.
        foreach ($task->hosts as $host) {
            $process = $this->getProcess($host, $task);

            $processes[$process[0]] = $process[1];
        }

        // Next we'll loop through the processes and run them sequentially while taking
        // the output and feeding it through the callback. This will in turn display
        // the output back out to the screen for the developer to inspect closely.
        foreach ($processes as $host => $process) {
            $process->run(function ($type, $output) use ($host, $callback) {
                $callback($type, $host, $output);
            });
        }

        return $this->gatherExitCodes($processes);
    }
}
