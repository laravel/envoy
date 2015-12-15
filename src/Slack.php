<?php

namespace Laravel\Envoy;

use Httpful\Request;

class Slack
{
    use ConfigurationParser;

    public $hook;
    public $channel;
    public $message;
    public $icon_emoji;
    public $username;

    /**
     * Create a new Slack instance.
     *
     * @param  string  $hook
     * @param  mixed  $channel
     * @param  string  $message
     * @return void
     */
    public function __construct($hook, $channel = '', $message = null, $icon_emoji = null, $username = null)
    {
        $this->hook = $hook;
        $this->channel = $channel;
        $this->message = $message;
        $this->icon_emoji = $icon_emoji;
        $this->username = $username;
    }

    /**
     * Create a new Slack message instance.
     *
     * @param  string  $hook
     * @param  mixed   $channel
     * @param  string  $message
     * @param  string  $icon_emoji
     * @param  string  $username
     * @return \Laravel\Envoy\Slack
     */
    public static function make($hook, $channel = '', $message = null, $icon_emoji = null, $username = null)
    {
        return new static($hook, $channel, $message, $icon_emoji, $username);
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
        
        if(! is_null($this->icon_emoji)) {
            $payload['icon_emoji'] = $this->icon_emoji;
        }
        
        if(! is_null($this->username)) {
            $payload['username'] = $this->username;
        }

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
