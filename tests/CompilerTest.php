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

    public function test_compile_before_statement()
    {
        $str = <<<'EOL'
@before
    echo "Running {{ $task }} task.";
@endbefore
EOL;
        $compiler = new Compiler();
        $result = $compiler->compile($str);

        $this->assertSame(1, preg_match('/\$__container->before\(.*?\}\);/s', $result, $matches));
    }
}
