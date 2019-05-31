<?php

namespace Laravel\Envoy;

use Httpful\Request;

class Wechat
{
    use ConfigurationParser;

    /**
     * support message type
     */
    const TEXT_MESSAGE = 'text';
    const MARKDOWN_MESSAGE = 'markdown';

    public $hook;
    public $message;
    public $options;

    protected $task;

    /**
     * Create a new Wechat instance.
     *
     * @param  string  $hook
     * @param  string  $message
     * @param  array   $options
     * @return void
     */
    public function __construct($hook, $message = null, $options = [])
    {
        $this->hook = $hook;
        $this->message = $message;
        $this->options = $options;
    }

    /**
     * Create a new Wechat message instance.
     *
     * @param  string  $hook
     * @param  string  $message
     * @param  array   $options
     * @return \Laravel\Envoy\Wechat
     */
    public static function make($hook, $message = null, $options = [])
    {
        return new static($hook, $message, $options);
    }

    /**
     * Send the Wechat message.
     *
     * @return void
     */
    public function send()
    {
        $message = $this->message ?: ($this->task ? ucwords($this->getSystemUser()).' ran the ['.$this->task.'] task.' : ucwords($this->getSystemUser()).' ran a task.');

        $messageType = $this->options['type'] ?? self::TEXT_MESSAGE;
        unset($this->options['type']);

        $payload = ['msgtype' => $messageType, $messageType => array_merge(['content' => $this->message], $this->options)];

        $res = Request::post("{$this->hook}")->sendsJson()->body($payload)->send();
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