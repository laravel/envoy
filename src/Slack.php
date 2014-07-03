<?php namespace Laravel\Envoy;

use Httpful\Request;

class Slack {

	use ConfigurationParser;

	public $team;
	public $token;
	public $channel;
	public $message;

	/**
	 * Create a new Slack instance.
	 *
	 * @param  string  $team
	 * @param  string  $token
	 * @param  mixed  $channel
	 * @param  string  $message
	 * @return void
	 */
	public function __construct($team, $token, $channel = '', $message = null)
	{
		$this->team = $team;
		$this->token = $token;
		$this->channel = $channel;
		$this->message = $message;
	}

	/**
	 * Create a new Slack message instance.
	 *
	 * @param  string  $team
	 * @param  string  $token
	 * @param  mixed   $channel
	 * @param  string  $message
	 * @return \Laravel\Envoy\Slack
	 */
	public static function make($team, $token, $channel = '', $message = null)
	{
		return new static($team, $token, $channel, $message);
	}

	/**
	 * Send the Slack message.
	 *
	 * @return void
	 */
	public function send()
	{
		$message = $this->message ?: ucwords($this->getSystemUser()).' ran the ['.$this->task.'] task.';

		$payload = ['text' => $message, 'channel' => $this->channel];

        Request::post("https://{$this->team}.slack.com/services/hooks/incoming-webhook?token={$this->token}")->sendsJson()->body($payload)->send();
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
