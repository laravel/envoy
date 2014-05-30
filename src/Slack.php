<?php namespace Laravel\Envoy;

use Httpful\Request;

class Slack {

	use ConfigurationParser;

	public $team;
	public $token;
	public $channel;
	public $message;
	public $options;

	/**
	 * Create a new Slack instance.
	 *
	 * @param  string  $team
	 * @param  string  $token
	 * @param  mixed  $channel
	 * @param  string  $message
	 * @param  array  $options
	 * @return void
	 */
	public function __construct($team, $token, $channel = '', $message = null, $options = array())
	{
		$this->team = $team;
		$this->token = $token;
		$this->channel = $channel;
		$this->message = $message;
		$this->options = $options;
	}

	/**
	 * Create a new Slack message instance.
	 *
	 * @param  string  $team
	 * @param  string  $token
	 * @param  mixed   $channel
	 * @param  string  $message
	 * @param  array  $options
	 * @return \Laravel\Envoy\Slack
	 */
	public static function make($team, $token, $channel = '', $message = null, $options = array())
	{
		return new static($team, $token, $channel, $message, $options);
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

		$payload = array_merge($payload, $this->options);

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
