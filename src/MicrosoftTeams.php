<?php

namespace Laravel\Envoy;

use GuzzleHttp\Client;

class MicrosoftTeams
{
    use ConfigurationParser;

    /**
     * The Teams WebHook URL.
     *
     * @var string
     */
    public $hook;

    /**
     * The message for Teams Custom Card.
     *
     * @var string
     */
    public $message;

    /**
     * The theme color for Teams Custom Card.
     *
     * @var string
     */
    public $theme;

    /**
     * The extra options for Teams Custom Card.
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
     * Create a new Teams instance.
     *
     * @param  string  $hook
     * @param  string  $message
     * @param  string  $theme
     * @param  array  $options
     * @return void
     */
    public function __construct($hook, $message = null, $theme = 'success', $options = [])
    {
        $this->hook = $hook;
        $this->message = $message;
        $this->theme = $theme;
        $this->options = $options;
    }

    /**
     * Create a new Teams message instance.
     *
     * @param  string  $hook
     * @param  string  $message
     * @param  string  $theme
     * @param  array  $options
     * @return \Laravel\Envoy\MicrosoftTeams
     */
    public static function make($hook, $message = null, $theme = 'success', $options = [])
    {
        return new static($hook, $message, $theme, $options);
    }

    /**
     * Send the Teams message.
     *
     * @return void
     */
    public function send()
    {
        (new Client())->post(
            $this->hook,
            [
                'json' => $this->buildPayload(),
            ]
        );
    }

    /**
     * Build the payload to send to the webhook.
     *
     * @return array
     */
    private function buildPayload()
    {
        $message = $this->message ?: ($this->task ? ucwords($this->getSystemUser()).' ran the ['.$this->task.'] task.' : ucwords($this->getSystemUser()).' ran a task.');

        return array_merge(
            [
                '@context' => 'https://schema.org/extensions',
                '@type' => 'MessageCard',
                'themeColor' => $this->getTheme(),
                'text' => $message,
            ],
            $this->options
        );
    }

    /**
     * Get the theme color for Teams Custom Card.
     *
     * @return string
     */
    private function getTheme(): string
    {
        $themes = [
            'success' => '#198754',
            'info' => '#0dcaf0',
            'error' => '#dc3545',
            'warning' => '#fd7e14',
        ];

        return $themes[$this->theme];
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
