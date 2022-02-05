<?php

namespace Laravel\Envoy\Tests;

use Laravel\Envoy\Compiler;
use PHPUnit\Framework\TestCase;

class CompilerTest extends TestCase
{
    public function test_it_compiles_finished_statement()
    {
        $str = <<<'EOL'
@finished
    echo 'shutdown';
@endfinished
EOL;
        $compiler = new Compiler();
        $result = $compiler->compile($str);

        $this->assertSame(1, preg_match('/\$__container->finished\(.*?\}\);/s', $result, $matches));
    }

    public function test_compile_servers_statement()
    {
        $str = <<<'EOL'
@servers(['local' => '127.0.0.1', 'remote' => '1.1.1.1'])
EOL;
        $compiler = new Compiler();
        $result = $compiler->compile($str);

        $this->assertStringContainsString(
            "\$__container->servers(['local' => '127.0.0.1', 'remote' => '1.1.1.1']);",
            $result
        );
    }

    public function test_compile_servers_statement_with_line_breaks()
    {
        $str = <<<'EOL'
@servers([
    'local' => '127.0.0.1',
    'remote' => '1.1.1.1',
])
EOL;
        $compiler = new Compiler();
        $result = $compiler->compile($str);

        $this->assertStringContainsString(
            "\$__container->servers([\n    'local' => '127.0.0.1',\n    'remote' => '1.1.1.1',\n]);",
            $result
        );
    }
}
