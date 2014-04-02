<?php namespace Laravel\Envoy;

use Httpful\Request;

class Rollbar {

	use ConfigurationParser;

	protected $token;
	protected $environment;
	protected $revision;
	protected $username;
	protected $comment;

	/**
	 * Create a new Rollbar instance.
	 *
	 * @param  string  $token
	 * @param  string  $environment
	 * @param  string  $revision
	 * @param  string  $username
	 * @param  string  $comment
	 * @return void
	 */
	public function __construct($token, $environment, $revision, $username = null, $comment = null)
	{
		$this->token = $token;
		$this->environment = $environment;
		$this->revision = $revision;
		$this->username = $username;
		$this->comment = $comment;
	}

	/**
	 * Create a new Rollbar instance.
	 *
	 * @param  string  $token
	 * @param  string  $environment
	 * @param  string  $revision
	 * @param  string  $username
	 * @param  string  $comment
	 * @return \Laravel\Envoy\Rollbar
	 */
	public static function make($token, $environment, $revision, $username = null, $comment = null)
	{
		return new static($token, $environment, $revision, $username, $comment);
	}

	/**
	 * Send to the Rollbar deploy hook.
	 *
	 * @return void
	 */
	public function send()
	{
		Request::post('https://api.rollbar.com/api/1/deploy')->send();
	}

	/**
	 * Set the task.
	 *
	 * @param  string  $task
	 * @return void
	 */
	public function task($task)
	{
		$this->task = $task;

		return $this;
	}
}
