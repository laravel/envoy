<?php namespace Laravel\Envoy;

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
     * @param  string  $string
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

        foreach (explode(PHP_EOL, $string) as $line) {
            $line = trim($line);

            // If the line is empty, we'll increment the group counter so we start a new group
            // on the next iteration through the loop. New blank lines indicate the divider
            // between various groups of options. Otherwise, we'll keep parsing the line.
            if ($line == '') {
                $index++;
            }

            // If the line begins with a comment, we will just skip that line then continue to
            // process the rest of the file. All comments in SSH config file start with the
            // hash so we can easily identify them here and continue with our iterations.
            elseif (starts_with($line, '#')) {
                continue;
            }

            // If the key is separated by an equals sign, we'll parse it into the two sections
            // and add it the current group. Items will be specified using either an equals
            // otherwise they will separated by any number of spaces which we will check.
            elseif (preg_match('/^\s*(\S+)\s*=(.*)$/', $line, $match)) {
                $groups[$index][$match[1]] = $match[2];
            }

            // Finally, the options must get separated by spaces if no other checks have found
            // the option. We will split by the first group of spaces then set the items on
            // the current group in the array. Then we'll return the SSH config instance.
            else {
                $segments = preg_split('/\s+/', $line, 2);

                $groups[$index][$segments[0]] = $segments[1];
            }
        }

        return new SSHConfigFile(array_values($groups));
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
            if ((isset($group['Host']) == $host && $group['Host'] == $host) ||
                (isset($group['Hostname']) && $group['Hostname'] == $host)) {
                if (! empty($user) && isset($group['User']) && $group['User'] != $user) {
                    continue;
                }

                return $group['Host'];
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
}
