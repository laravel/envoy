<?php namespace Laravel\Envoy;

use Httpful\Request;

class Hall {

	use ConfigurationParser;

	public $token;
	public $from;
	public $message;

	public static $defaultFrom = 'Laravel Envoy';

	/**
	 * Create a new Hall instance.
	 *
	 * @param  string       $token    Room API token
	 * @param  string|null  $from
	 * @param  string|null  $message
	 * @return void
	 */
	public function __construct($token, $from = null, $message = null)
	{
		$this->token = $token;
		$this->from = $from;
		$this->message = $message;
	}

	/**
	 * Create a new Hall message instance.
	 *
	 * @param  string       $token   Room API token
	 * @param  string|null  $from
	 * @param  string|null  $message
	 * @return \Laravel\Envoy\Hipchat
	 */
	public static function make($token, $from = null, $message = null)
	{
		return new static($token, $from, $message);
	}
	
	/**
	 * Send the Hall message.
	 *
	 * @return void
	 */
	public function send()
	{
		$message = $this->message ?: ucwords($this->getSystemUser()).' ran the ['.$this->task.'] task.';
		$from = $this->from ?: static::$defaultFrom;

		$payload = [
			'title' => $from,
			'message' => $message
			];

		Request::post('https://hall.com/api/1/services/generic/' . $this->token)
			->sendsJson()
			->body(json_encode($payload))
			->send();
	}

	/**
	 * Set the task for the message.
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
