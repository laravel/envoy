<?php namespace Laravel\Envoy;

use Httpful\Request;

class Flowdock {

    use ConfigurationParser;

    public $token;
    public $source;
    public $fromAddress;
    public $tags;
    public $subject;
    public $link;
    public $message;

    /**
     * Create a new Flowdock instance.
     *
     * @param  string  $token
     * @param  string  $source
     * @param  string  $fromAddress
     * @param  string  $tags
     * @param  string  $subject
     * @param  string  $link
     * @param  string  $message
     * @return void
     */
    public function __construct($token, $source, $fromAddress, $tags = null, $subject = null, $link = null, $message = null)
    {
        $this->token       = $token;
        $this->source      = $source;
        $this->fromAddress = $fromAddress;
        $this->tags        = $tags;
        $this->subject     = $subject;
        $this->link        = $link;
        $this->message     = $message;
    }

    /**
     * Create a new Flowdock message instance.
     *
     * @param  string  $token
     * @param  string  $source
     * @param  string  $fromAddress
     * @param  string  $tags
     * @param  string  $subject
     * @param  string  $link
     * @param  string  $message
     * @return \Laravel\Envoy\Flowdock
     */
    public static function make($token, $source, $fromAddress, $tags = null, $subject = null, $link = null, $message = null)
    {
        return new static($token, $source, $fromAddress, $tags, $subject, $link, $message);
    }

    /**
     * Send the Flowdock message.
     *
     * @return void
     */
    public function send()
    {
        $message = $this->message ?: ucwords($this->getSystemUser()).' ran the ['.$this->task.'] task.';
        $subject = $this->subject ?: $message;

        $payload = [
            'source'       => $this->source,
            'from_address' => $this->fromAddress,
            'subject'      => $subject,
            'content'      => $message,
            'tags'         => $this->tags,
            'link'         => $this->link,
        ];

        // Send mail-like messages to the team inbox of a flow. https://www.flowdock.com/api/team-inbox
        Request::post("https://api.flowdock.com/v1/messages/team_inbox/{$this->token}")->sendsJson()->body($payload)->send();
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
