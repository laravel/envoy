<?php

namespace Laravel\Envoy;

trait ConfigurationParser
{
    /**
     * Get the configured server from the SSH config.
     *
     * @param  string  $host
     * @return string|null
     */
    protected function getConfiguredServer($host)
    {
        if ($config = $this->getSshConfig($this->getSystemUser())) {
            return $config->findConfiguredHost($host);
        }
    }

    /**
     * Get the SSH configuration file instance.
     *
     * @param  string  $user
     * @return \Laravel\Envoy\SSHConfigFile|null
     */
    protected function getSshConfig($user)
    {
        if (file_exists($path = $this->getHomeDirectory($user).'/.ssh/config')) {
            return SSHConfigFile::parse($path);
        }
    }

    /**
     * Get the home directory for the user based on OS.
     *
     * @param  string  $user
     * @return string|null
     */
    protected function getHomeDirectory($user)
    {
        $system = strtolower(php_uname());

        if (str_contains($system, 'darwin')) {
            return "/Users/{$user}";
        } elseif (str_contains($system, 'windows')) {
            return "C:\Users\{$user}";
        } elseif (str_contains($system, 'linux')) {
            return "/home/{$user}";
        }
    }

    /**
     * Get the system user.
     *
     * @return string
     */
    protected function getSystemUser()
    {
        if (str_contains(strtolower(php_uname()), 'windows')) {
            return getenv('USERNAME');
        }

        return posix_getpwuid(posix_geteuid())['name'];
    }

    /**
     * Determine if the given value is a valid IP.
     *
     * @param  string  $value
     * @return bool
     */
    protected function isValidIp($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
}
