<?php

namespace Laravel\Envoy;

use Httpful\Request;

class Slack
{
    use ConfigurationParser;

    public $hook;
    public $payload;

    /**
     * Create a new Slack instance.
     *
     * @param  string $hook
     * @param  mixed  $payload
     * @param  string $message
     *
     * @internal param string $hook
     */
    public function __construct($hook, $payload = '', $message = null)
    {
        $this->hook = $hook;

        $payload_defaults = [
            'username' => 'Laravel Envoy',
            'channel' => '',
            'text' => null,
        ];

        if (! is_array($payload)) {
            $payload = [
                'channel' => $payload,
                'text' => $message,
            ];
        }

        $this->payload = array_merge($payload_defaults, $payload);
    }

    /**
     * Create a new Slack message instance.
     *
     * @param  string  $hook
     * @param  mixed   $payload
     * @param  string  $message
     * @return \Laravel\Envoy\Slack
     */
    public static function make($hook, $payload = '', $message = null)
    {
        return new static($hook, $payload, $message);
    }

    /**
     * Send the Slack message.
     *
     * @return void
     */
    public function send()
    {
        if (is_null($this->payload['text'])) {
            $this->payload['text'] = ucwords($this->getSystemUser()).' ran the ['.$this->task.'] task.';
        }
        Request::post("{$this->hook}")->sendsJson()->body($this->payload)->send();
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
