<?php

namespace Laravel\Envoy;

class SSHConfigFileTest extends TestCase
{
    public function test_it_removes_leading_and_trailing_whitespace_on_each_line()
    {
        $config = <<<EOT
 Host=bar
\tHostname=baz.com
EOT;
        $groups = $this->parse($config);

        $this->assertEquals([[
            'host' => 'bar',
            'hostname' => 'baz.com',
        ]], $groups);
    }

    public function test_it_splits_keywords_and_arguments_by_equal_sign()
    {
        $config = <<<'EOT'
Host=bar
Hostname=baz.com
EOT;
        $groups = $this->parse($config);

        $this->assertEquals([[
            'host' => 'bar',
            'hostname' => 'baz.com',
        ]], $groups);
    }

    public function test_it_splits_keywords_and_arguments_by_whitespace()
    {
        $config = <<<EOT
Host bar
Hostname\tbaz.com
EOT;
        $groups = $this->parse($config);

        $this->assertEquals([[
            'host' => 'bar',
            'hostname' => 'baz.com',
        ]], $groups);
    }

    public function test_it_lowercases_keywords_not_arguments()
    {
        $config = <<<'EOT'
Host Bar
Hostname baz.com
EOT;
        $groups = $this->parse($config);

        $this->assertEquals([[
            'host' => 'Bar',
            'hostname' => 'baz.com',
        ]], $groups);
    }

    public function test_it_unquotes_arguments_and_reserves_whitespace()
    {
        $config = <<<'EOT'
Host "bar"
Hostname "baz.com"
IdentityFile="path "to/file""
EOT;
        $groups = $this->parse($config);

        $this->assertEquals([[
            'host' => 'bar',
            'hostname' => 'baz.com',
            'identityfile' => 'path "to/file"',
        ]], $groups);
    }

    public function test_it_ignores_comments_and_empty_lines_outside_host_section()
    {
        $config = <<<'EOT'
# Foo

Host bar
Hostname baz.com
EOT;
        $groups = $this->parse($config);

        $this->assertEquals([[
            'host' => 'bar',
            'hostname' => 'baz.com',
        ]], $groups);
    }

    public function test_it_ignores_comments_and_empty_lines_inside_host_section()
    {
        $config = <<<'EOT'
Host bar
# Foo

Hostname baz.com
# Qux
Port 1234
EOT;
        $groups = $this->parse($config);

        $this->assertEquals([[
            'host' => 'bar',
            'hostname' => 'baz.com',
            'port' => 1234,
        ]], $groups);
    }

    public function test_it_parses_sections_without_new_lines_between_them()
    {
        $config = <<<'EOT'
Host bar
Hostname baz.com
Host qux
Hostname qux.com
EOT;
        $groups = $this->parse($config);

        $this->assertEquals([[
            'host' => 'bar',
            'hostname' => 'baz.com',
        ], [
            'host' => 'qux',
            'hostname' => 'qux.com',
        ]], $groups);
    }

    public function test_it_parses_sections_separated_by_new_lines()
    {
        $config = <<<'EOT'
Host bar
Hostname baz.com

Host qux
Hostname qux.com
EOT;
        $groups = $this->parse($config);

        $this->assertEquals([[
            'host' => 'bar',
            'hostname' => 'baz.com',
        ], [
            'host' => 'qux',
            'hostname' => 'qux.com',
        ]], $groups);
    }

    public function test_it_parses_sections_separated_by_match_keyword()
    {
        $config = <<<'EOT'
Match host somehost exec "test %p = 42"
    IdentityFile ~/.ssh/id_rsa1
Host bar
    Hostname baz.com
Match user john
    IdentityFile ~/.ssh/id_rsa2
Host qux
    Hostname qux.com
Match localuser me
    IdentityFile ~/.ssh/id_rsa3
EOT;
        $groups = $this->parse($config);

        $this->assertEquals([[
            'host' => 'bar',
            'hostname' => 'baz.com',
        ], [
            'host' => 'qux',
            'hostname' => 'qux.com',
        ]], $groups);
    }

    public function test_it_finds_a_matching_host_without_user()
    {
        $config = <<<'EOT'
Host bar
Hostname baz.com
Host qux
Hostname qux.com
EOT;
        $sshConfig = SSHConfigFile::parseString($config);
        $host = $sshConfig->findConfiguredHost('bar');

        $this->assertEquals('bar', $host);
    }

    public function test_it_finds_a_matching_host_by_hostname()
    {
        $config = <<<'EOT'
Host bar
Hostname baz.com
Host qux
Hostname qux.com
EOT;
        $sshConfig = SSHConfigFile::parseString($config);
        $host = $sshConfig->findConfiguredHost('baz.com');

        $this->assertEquals('bar', $host);
    }

    public function test_it_returns_null_if_there_are_no_matching_hosts()
    {
        $config = <<<'EOT'
Host bar
Hostname baz.com
Host qux
Hostname qux.com
EOT;
        $sshConfig = SSHConfigFile::parseString($config);
        $host = $sshConfig->findConfiguredHost('none');

        $this->assertNull($host);
    }

    public function test_it_finds_a_matching_host_if_user_specified_and_matches_config()
    {
        $config = <<<'EOT'
Host bar
Hostname baz.com
User john
EOT;
        $sshConfig = SSHConfigFile::parseString($config);
        $host = $sshConfig->findConfiguredHost('john@bar');

        $this->assertEquals('bar', $host);
    }

    public function test_it_returns_null_for_a_matching_host_if_user_specified_and_is_different_from_one_in_config()
    {
        $config = <<<'EOT'
Host bar
Hostname baz.com
User john
EOT;
        $sshConfig = SSHConfigFile::parseString($config);
        $host = $sshConfig->findConfiguredHost('jane@bar');

        $this->assertNull($host);
    }

    private function parse($config)
    {
        $sshConfig = SSHConfigFile::parseString($config);

        $r = new \ReflectionObject($sshConfig);
        $property = $r->getProperty('groups');
        $property->setAccessible(true);

        return $property->getValue($sshConfig);
    }
}
