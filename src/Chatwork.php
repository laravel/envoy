<?php namespace Laravel\Envoy;

use Httpful\Request;

class Chatwork {

	use ConfigurationParser;

	public $token;
	public $room;
	public $message;

	/**
	 * Create a new Chatwork instance.
	 *
	 * @param  string  $token
	 * @param  string  $room
	 * @param  string  $message
	 * @return void
	 */
	public function __construct($token, $room, $message = null)
	{
		$this->room = $room;
		$this->token = $token;
		$this->message = $message;
	}

	/**
	 * Create a new Chatwork message instance.
	 *
	 * @param  string  $token
	 * @param  string  $room
	 * @param  string  $message
	 * @return \Laravel\Envoy\Chatwork
	 */
	public static function make($token, $room, $message = null)
	{
		return new static($token, $room, $message);
	}

	/**
	 * Send the message.
	 *
	 * @return void
	 */
	public function send()
	{
		$message = $this->message ?: ucwords($this->getSystemUser()).' ran the ['.$this->task.'] task.';

		$payload = ['body' => $message];

		Request::post("https://api.chatwork.com/v1/rooms/{$this->room}/messages", http_build_query($payload))->addHeader('X-ChatWorkToken', $this->token)->send();
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
