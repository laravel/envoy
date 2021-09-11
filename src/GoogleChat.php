<?php

namespace Laravel\Envoy;

use GuzzleHttp\Client;

class GoogleChat
{
    use ConfigurationParser;

    /**
     * The webhook URL.
     *
     * @var string
     */
    public $hook;

    /**
     * The message.
     *
     * @var string|array
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
     * Create a new GoogleChat instance.
     *
     * @param  string  $hook
     * @param  string|array  $message
     * @param  array  $options
     * @return void
     */
    public function __construct($hook, $message = null, $options = [])
    {
        $this->hook = $hook;
        $this->message = $message;
        $this->options = $options;
    }

    /**
     * Create a new GoogleChat message instance.
     *
     * @param  string  $hook
     * @param  string|array  $message
     * @param  array  $options
     * @return \Laravel\Envoy\GoogleChat
     */
    public static function make($hook, $message = null, $options = [])
    {
        return new static($hook, $message, $options);
    }

    /**
     * Send the GoogleChat message.
     *
     * @return void
     */
    public function send()
    {
        $message = $this->message ?: ($this->task ? ucwords($this->getSystemUser()) . ' ran the [' . $this->task . '] task.' : ucwords($this->getSystemUser()) . ' ran a task.');

        $payload = array_merge(
            is_string($message) ? ['text' => $message] : $message,
            $this->options
        );

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
