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

    public function test_it_compiles_server_statement()
    {
        $str = <<<'EOL'
@servers([
    'foo' => 'bar'
])
EOL;
        $compiler = new Compiler();
        $result = $compiler->compile($str);

        $this->assertSame($result, "<?php \$__container->servers(['foo'=>'bar']); ?>");

        $str = <<<'EOL'
@servers([
    'foo' => [
        'bar',
        'baz',
        'bah'
    ]
])
EOL;
        $compiler = new Compiler();
        $result = $compiler->compile($str);

        $this->assertSame($result, "<?php \$__container->servers(['foo'=>['bar','baz','bah']]); ?>");

        $str = <<<'EOL'
@servers(['foo' => 'bar'])
EOL;
        $compiler = new Compiler();
        $result = $compiler->compile($str);

        $this->assertSame($result, "<?php \$__container->servers(['foo'=>'bar']); ?>");
    }
}
