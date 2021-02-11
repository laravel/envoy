<?php

namespace Laravel\Envoy;

class Task
{
    /**
     * All of the servers to run the task on.
     *
     * @var array
     */
    public $servers = [];

    /**
     * The username the task should be run as.
     *
     * @var string
     */
    public $user;

    /**
     * The script commands.
     *
     * @var string
     */
    public $script;

    /**
     * Indicates if the task should be run in parallel across servers.
     *
     * @var array
     */
    public $parallel;

    /**
     * Asks a user for a confirmation.
     *
     * @var string
     */
    public $confirm;

    /**
     * Create a new Task instance.
     *
     * @param  array  $servers
     * @param  string  $user
     * @param  string  $script
     * @param  bool  $parallel
     * @param  string|null  $confirm
     * @return void
     */
    public function __construct(array $servers, $user, $script, $parallel = false, $confirm = null)
    {
        $this->user = $user;
        $this->servers = $servers;
        $this->script = $script;
        $this->parallel = $parallel;
        $this->confirm = $confirm;
    }
}
