<?php namespace Laravel\Envoy;

use Httpful\Request;

class Flowdock {

    use ConfigurationParser;

    public $token;
    public $external_user_name;
    public $tags;
    public $message;

    /**
     * Create a new Flowdock instance.
     *
     * @param  string  $token
     * @param  string  $external_user_name
     * @param  string  $tags
     * @param  string  $message
     * @return void
     */
    public function __construct($token, $external_user_name, $tags = null, $message = null)
    {
        $this->token              = $token;
        $this->external_user_name = $external_user_name;
        $this->tags               = $tags;
        $this->message            = $message;
    }

    /**
     * Create a new Flowdock message instance.
     *
     * @param  string  $token
     * @param  string  $external_user_name
     * @param  string  $tags
     * @param  string  $message
     * @return \Laravel\Envoy\Flowdock
     */
    public static function make($token, $external_user_name, $tags = null, $message = null)
    {
        return new static($token, $external_user_name, $tags, $message);
    }

    /**
     * Send the Flowdock message.
     *
     * @return void
     */
    public function send()
    {
        $message = $this->message ?: ucwords($this->getSystemUser()).' ran the ['.$this->task.'] task.';

        $payload = [
            'content' => $message,
            'external_user_name' => $this->external_user_name,
            'tags' => $this->tags
        ];

        // Post messages to a flow's chat from an external user https://www.flowdock.com/api/chat
        Request::post("https://api.flowdock.com/v1/messages/chat/{$this->token}")->sendsJson()->body($payload)->send();
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
