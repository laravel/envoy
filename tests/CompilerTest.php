<?php

namespace Laravel\Envoy;

class CompilerTest extends TestCase
{
    public function test_it_compiles_finished_statement()
    {
        $str = <<<EOL
@finished
    echo 'shutdown';
@endfinished
EOL;
        $compiler = new Compiler();
        $result = $compiler->compile($str);
        $this->assertEquals(1, preg_match('/\$__container->finished\(.*?\}\);/s', $result, $matches));
    }
}
