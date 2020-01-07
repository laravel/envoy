<?php

namespace Laravel\Envoy;

use GuzzleHttp\Client;

class Slack
{
    use ConfigurationParser;

    /**
     * The webhook URL.
     *
     * @var string
     */
    public $hook;

    /**
     * The Slack channel.
     *
     * @var mixed
     */
    public $channel;

    /**
     * The message.
     *
     * @var string
     */
    public $message;

    /**
     * The options.
     *
     * @var array
     */
    public $options;

    /**
     * The task name.
     *
     * @var string
     */
    protected $task;

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
     * @param  mixed  $channel
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
        $message = $this->message ?: ($this->task ? ucwords($this->getSystemUser()).' ran the ['.$this->task.'] task.' : ucwords($this->getSystemUser()).' ran a task.');

        $payload = array_merge(['text' => $message, 'channel' => $this->channel], $this->options);

        (new Client())->post($this->hook, [
            'json' => $payload,
        ]);
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
