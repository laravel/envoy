<?php

namespace Laravel\Envoy;

use Httpful\Request;

class Hipchat
{
    use ConfigurationParser;

    public $token;
    public $room;
    public $from;
    public $message;
    public $color;

    /**
     * Create a new Hipchat instance.
     *
     * @param  string  $token
     * @param  mixed  $room
     * @param  string  $from
     * @param  string  $message
     * @param  string  $color
     * @return void
     */
    public function __construct($token, $room, $from, $message = null, $color = 'purple')
    {
        $this->room = $room;
        $this->from = $from;
        $this->token = $token;
        $this->message = $message;
        $this->color = $color;
    }

    /**
     * Create a new HipChat message instance.
     *
     * @param  string  $token
     * @param  mixed  $room
     * @param  string  $from
     * @param  string  $message
     * @param  string  $color
     * @return \Laravel\Envoy\Hipchat
     */
    public static function make($token, $room, $from, $message = null, $color = 'purple')
    {
        return new static($token, $room, $from, $message, $color);
    }

    /**
     * Send the HipChat message.
     *
     * @return mixed
     */
    public function send()
    {
        $message = $this->message ?: ucwords($this->getSystemUser()).' ran the ['.$this->task.'] task.';

        $format = $message != strip_tags($message) ? 'html' : 'text';

        $query = ['auth_token' => $this->token];

        $payload = [
            'from' => $this->from, 'message' => $message,
            'message_format' => $format, 'notify' => true, 'color' => $this->color,
        ];

        return Request::post('https://api.hipchat.com/v2/room/'.$this->room.'/notification?'.http_build_query($query))
            ->sendsJson()
            ->body(json_encode($payload))
            ->send();
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
