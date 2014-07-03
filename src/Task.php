<?php namespace Laravel\Envoy;

class Task {

	/**
	 * All of the hosts to run the task on.
	 *
	 * @var array
	 */
	public $hosts = [];

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
	 * Create a new Task instance.
	 *
	 * @param  array  $hosts
	 * @param  string  $user
	 * @param  string  $script
	 * @return void
	 */
	public function __construct(array $hosts, $user, $script, $parallel = false)
	{
		$this->user = $user;
		$this->hosts = $hosts;
		$this->script = $script;
		$this->parallel = $parallel;
	}

}
