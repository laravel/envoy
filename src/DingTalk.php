<?php

namespace Laravel\Envoy;

use Httpful\Request;

class DingTalk
{
    use ConfigurationParser;

    public $hook;
    public $atAll;
    public $message;

    protected $task;

    /**
     * Create a new DingTalk instance.
     *
     * @param  string  $hook
     * @param  mixed  $channel
     * @param  string  $message
     * @return void
     */
    public function __construct($hook, $atAll = false, $message = null)
    {
        $this->hook = $hook;
        $this->atAll = $atAll;
        $this->message = $message;
    }

    /**
     * Create a new DingTalk message instance.
     *
     * @param  string  $hook
     * @param  mixed   $channel
     * @param  string  $message
     * @param  array  $options
     * @return \Laravel\Envoy\DingTalk
     */
    public static function make($hook, $atAll = '', $message = null)
    {
        return new static($hook, $atAll, $message);
    }

    /**
     * Send the DingTalk message.
     *
     * @return void
     */
    public function send()
    {
        $message = $this->message ?: ($this->task ? ucwords($this->getSystemUser()).' ran the ['.$this->task.'] task.' : ucwords($this->getSystemUser()).' ran a task.');

        $payload = ['msgtype' => 'text',
                    'text' => ['content' => $message],
                    'at' => [],
                    'isAtAll' => $this->atAll
                ];
        $headers = ['Content-Type' => 'application/json;charset=utf-8'];
        Request::post("{$this->hook}",$headers)->sendsJson()->body($payload)->send();
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
