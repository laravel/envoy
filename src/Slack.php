<?php namespace Laravel\Envoy;

use Httpful\Request;

class Slack
{
    use ConfigurationParser;

    public $hook;
    public $channel;
    public $message;

    /**
     * Create a new Slack instance.
     *
     * @param  string  $hook
     * @param  mixed  $channel
     * @param  string  $message
     * @return void
     */
    public function __construct($hook, $channel = '', $message = null)
    {
        $this->hook = $hook;
        $this->channel = $channel;
        $this->message = $message;
    }

    /**
     * Create a new Slack message instance.
     *
     * @param  string  $hook
     * @param  mixed   $channel
     * @param  string  $message
     * @return \Laravel\Envoy\Slack
     */
    public static function make($hook, $channel = '', $message = null)
    {
        return new static($hook, $channel, $message);
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

        Request::post("{$this->hook}")->sendsJson()->body($payload)->send();
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
