<?php

namespace Laravel\Envoy;

use Httpful\Request;

class Slack
{
    use ConfigurationParser;

    public $hook;
    public $channel;
    public $message;
    public $additional_payload;

    /**
     * Create a new Slack instance.
     *
     * @param  string  $hook
     * @param  mixed  $channel
     * @param  string  $message
     * @param  array  $additional_payload
     * @return void
     */
    public function __construct($hook, $channel = '', $message = null, $additional_payload = [])
    {
        $this->hook = $hook;
        $this->channel = $channel;
        $this->message = $message;
        $this->additional_payload = $additional_payload;
    }

    /**
     * Create a new Slack message instance.
     *
     * @param  string  $hook
     * @param  mixed   $channel
     * @param  string  $message
     * @param  array  $additional_payload
     * @return \Laravel\Envoy\Slack
     */
    public static function make($hook, $channel = '', $message = null, $additional_payload = [])
    {
        return new static($hook, $channel, $message, $additional_payload);
    }

    /**
     * Send the Slack message.
     *
     * @return void
     */
    public function send()
    {
        $message = $this->message ?: ucwords($this->getSystemUser()).' ran the ['.$this->task.'] task.';

        $payload = array_merge(['text' => $message, 'channel' => $this->channel], $this->additional_payload);

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
