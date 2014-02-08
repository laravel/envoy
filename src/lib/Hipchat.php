<?php namespace Laravel\Envoy;

use Httpful\Request;

class Hipchat {

	use ConfigurationParser;

	public $token;
	public $room;
	public $from;
	public $message;

	/**
	 * Create a new Hipchat instance.
	 *
	 * @param  string  $token
	 * @param  mixed  $room
	 * @param  string  $from
	 * @param  string  $message
	 * @return void
	 */
	public function __construct($token, $room, $from, $message = null)
	{
		$this->room = $room;
		$this->from = $from;
		$this->token = $token;
		$this->message = $message;
	}

	/**
	 * Create a new HipChat message instance.
	 *
	 * @param  string  $token
	 * @param  mixed  $room
	 * @param  string  $from
	 * @param  string  $message
	 * @return \Laravel\Envoy\Hipchat
	 */
	public static function make($token, $room, $from, $message = null)
	{
		return new static($token, $room, $from, $message);
	}

	/**
	 * Send the HipChat message.
	 *
	 * @return void
	 */
	public function send()
	{
		$message = $this->message ?: ucwords($this->getSystemUser()).' ran the ['.$this->task.'] task.';

		$payload = [
			'auth_token' => $this->token, 'room_id' => $this->room,
			'from' => $this->from, 'message' => $message,
			'message_format' => 'text', 'notify' => 1, 'color' => 'purple',
		];

		Request::get('https://api.hipchat.com/v1/rooms/message?'.http_build_query($payload))->send();
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