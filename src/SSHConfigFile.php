<?php

namespace Laravel\Envoy;

class SSHConfigFile
{
    /**
     * The SSH configuration groups.
     *
     * @var array
     */
    protected $groups = [];

    /**
     * Create a new SSH configuration file.
     *
     * @param  array  $groups
     * @return void
     */
    public function __construct(array $groups)
    {
        $this->groups = $groups;
    }

    /**
     * Parse the given configuration file.
     *
     * @param  string  $file
     * @return \Laravel\Envoy\SSHConfigFile
     */
    public static function parse($file)
    {
        return static::parseString(file_get_contents($file));
    }

    /**
     * Parse the given configuration string.
     *
     * @param  string  $string
     * @return \Laravel\Envoy\SSHConfigFile
     */
    public static function parseString($string)
    {
        $groups = [];

        $index = 0;

        $matchSection = false;

        foreach (explode(PHP_EOL, $string) as $line) {
            $line = trim($line);

            // If the line is empty or begins with a comment, we will just skip that line then
            // continue to process the rest of the file. All comments in SSH config file start
            // with the hash so we can easily identify them here and continue iterating.
            if ('' == $line || starts_with($line, '#')) {
                continue;
            }

            // If the key is separated by an equals sign, we'll parse it into the two sections
            // and add it the current group. Items will be specified using either an equals
            // otherwise they will separated by any number of spaces which we will check.
            if (preg_match('/^\s*(\S+)\s*=(.*)$/', $line, $match)) {
                $key = strtolower($match[1]);
                $value = $match[2];
            }

            // Finally, the options must get separated by spaces if no other checks have found
            // the option. We will split by the first group of spaces then set the items on
            // the current group in the array. Then we'll return the SSH config instance.
            else {
                $segments = preg_split('/\s+/', $line, 2);
                $key = strtolower($segments[0]);
                $value = $segments[1];
            }

            // Note that keywords are case-insensitive and arguments are case-sensitive.
            $key = strtolower($key);
            // Arguments may optionally be enclosed in double quotes (") in order to
            // represent arguments containing spaces.
            $value = self::unqoute($value);

            // The configuration file contains sections separated by Host and (or) Match
            // specifications. Therefore if we come across a Host keyword we start a new
            // group. If it's a Match we ignore declarations until next 'Host' section.
            if ('host' === $key) {
                $index++;
                $matchSection = false;
            } elseif ('match' === $key) {
                $matchSection = true;
            }

            // Collect only Host declarations
            if (!$matchSection) {
                $groups[$index][$key] = $value;
            }
        }

        return new self(array_values($groups));
    }

    /**
     * Get the configured SSH host by name or IP.
     *
     * @param  string  $host
     * @return string|null
     */
    public function findConfiguredHost($host)
    {
        list($user, $host) = $this->parseHost($host);

        foreach ($this->groups as $group) {
            if ((isset($group['host']) == $host && $group['host'] == $host) ||
                (isset($group['hostname']) && $group['hostname'] == $host)) {
                if (! empty($user) && isset($group['user']) && $group['user'] != $user) {
                    continue;
                }

                return $group['host'];
            }
        }
    }

    /**
     * Parse the host into user and host segments.
     *
     * @param  string  $host
     * @return array
     */
    protected function parseHost($host)
    {
        return str_contains($host, '@') ? explode('@', $host) : [null, $host];
    }

    /**
     * Unqoutes (removes) an optionally double quoted string
     * and preserves embed double quotes and whitespace
     *
     * @param string $string
     * @return string
     */
    private static function unqoute($string)
    {
        if ('"' === substr($string, 0, 1) && '"' === substr($string, -1, 1)) {
            return substr($string, 1, -1);
        }

        return $string;
    }
}
