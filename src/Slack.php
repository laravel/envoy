<?php

namespace Laravel\Envoy;

use Httpful\Request;

class Slack
{
    use ConfigurationParser;

    public $hook;
    public $channel;
    public $message;
    public $options;

    /**
     * Create a new Slack instance.
     *
     * @param  string  $hook
     * @param  mixed  $channel
     * @param  string  $message
     * @param  array  $options
     * @return void
     */
    public function __construct($hook, $channel = '', $message = null, $options = [])
    {
        $this->hook = $hook;
        $this->channel = $channel;
        $this->message = $message;
        $this->options = $options;
    }

    /**
     * Create a new Slack message instance.
     *
     * @param  string  $hook
     * @param  mixed   $channel
     * @param  string  $message
     * @param  array  $options
     * @return \Laravel\Envoy\Slack
     */
    public static function make($hook, $channel = '', $message = null, $options = [])
    {
        return new static($hook, $channel, $message, $options);
    }

    /**
     * Send the Slack message.
     *
     * @return void
     */
    public function send()
    {
        $message = $this->message ?: ucwords($this->getSystemUser()).' ran the ['.$this->task.'] task.';

        $payload = array_merge(['text' => $message, 'channel' => $this->channel], $this->options);

        Request::post("{$this->hook}")->sendsJson()->body($payload)->send();
    }

    /**
     * Set the task for the message.
     *
     * @param  string  $task
     * @return $this
     */
    public function task($task)
    {
        $this->task = $task;

        return $this;
    }
}
