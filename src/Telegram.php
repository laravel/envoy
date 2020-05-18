<?php

namespace Laravel\Envoy;

use GuzzleHttp\Client;

class Telegram
{
    use ConfigurationParser;

    /**
     * The Telegram bot API token.
     *
     * @var string
     */
    public $token;

    /**
     * The Telegram "chat_id".
     *
     * @var mixed
     */
    public $chat;

    /**
     * The message that should be sent.
     *
     * @var string
     */
    public $message;

    /**
     * The message options.
     *
     * @var array
     */
    public $options;

    /**
     * The name of the task.
     *
     * @var string
     */
    protected $task;

    /**
     * Create a new Telegram instance.
     *
     * @param  string  $token
     * @param  mixed  $chat
     * @param  string  $message
     * @param  array  $options
     * @return void
     */
    public function __construct($token, $chat, $message = null, $options = [])
    {
        $this->token = $token;
        $this->chat = $chat;
        $this->message = $message;
        $this->options = $options;
    }

    /**
     * Create a new Telegram message instance.
     *
     * @param  string  $token
     * @param  string  $chat
     * @param  string  $message
     * @param  array  $options
     * @return \Laravel\Envoy\Telegram
     */
    public static function make($token, $chat, $message = null, $options = [])
    {
        return new static($token, $chat, $message, $options);
    }

    /**
     * Send the Telegram message.
     *
     * @return void
     */
    public function send()
    {
        (new Client())->post($this->getSendMessageEndpoint(), [
            'json' => $this->buildPayload(),
        ]);
    }

    /**
     * Get the endpoint for the Send request.
     *
     * @return mixed
     */
    private function getSendMessageEndpoint()
    {
        return "https://api.telegram.org/bot{$this->token}/sendMessage";
    }

    /**
     * Build the payload to send to the endpoint.
     *
     * @return array
     */
    private function buildPayload()
    {
        $message = $this->message ?: ($this->task ? ucwords($this->getSystemUser()).' ran the ['.$this->task.'] task.' : ucwords($this->getSystemUser()).' ran a task.');

        return array_merge(['text' => $message, 'chat_id' => $this->chat], $this->options);
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
