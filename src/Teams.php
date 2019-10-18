<?php

namespace Laravel\Envoy;

use Httpful\Request;

class Teams
{
    use ConfigurationParser;

    public $hook;
    public $message;
    public $options;

    protected $task;

    /**
     * Create a new Microsoft Teams instance.
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
     * Create a new Teams message instance.
     *
     * @param  string  $hook
     * @param  string  $message
     * @param  array  $options
     * @return \Laravel\Envoy\Teams
     */
    public static function make($hook, $message = null, $options = [])
    {
        var_dump($options);exit;
        return new static($hook, $message, $options);
    }

    /**
     * Send the Teams message.
     *
     * @return void
     */
    public function send()
    {
        $message = $this->message ?: ($this->task ? ucwords($this->getSystemUser()).' ran the ['.$this->task.'] task.' : ucwords($this->getSystemUser()).' ran a task.');

        $payload = $this->card($message);

        return Request::post("{$this->hook}")->sendsJson()->body($payload)->send();
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

    /**
     * Build the card.
     *
     *
     */
    public function card($message)
    {
        $json = '{
          "@context": "https://schema.org/extensions",
          "@type": "MessageCard",
          "themeColor": "ed7b06",
          "title": "Laravel Envoy",
          "text": "'.$message.'"
        }';

        return $json;
    }
}
