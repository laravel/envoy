<?php

namespace Laravel\Envoy;

use Httpful\Request;

class Discord
{
    use ConfigurationParser;

    public $hook;
    public $message;
    public $options;

    protected $task;

    /**
     * Create a new Discord instance.
     *
     * @param  string  $hook
     * @param  string  $message
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
     * Create a new Discord message instance.
     *
     * @param  string  $hook
     * @param  string  $message
     * @param  array  $options
     * @return \Laravel\Envoy\Discord
     */
    public static function make($hook, $message = null, $options = [])
    {
        return new static($hook, $message, $options);
    }

    /**
     * Send the Discord message.
     *
     * @return void
     */
    public function send()
    {
        $message = $this->message ?: ($this->task ? ucwords($this->getSystemUser()).' ran the ['.$this->task.'] task.' : ucwords($this->getSystemUser()).' ran a task.');

        $payload = array_merge(['content' => $message], $this->options);

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
