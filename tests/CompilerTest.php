<?php

namespace Laravel\Envoy;

class CompilerTest extends TestCase
{
    public function test_it_compiles_shutdown_statement()
    {
        $str = <<<EOL
@shutdown
    echo 'shutdown';
@endshutdown
EOL;
        $compiler = new Compiler();
        $result = $compiler->compile($str);
        $this->assertEquals(1, preg_match('/\$__container->shutdown\(.*?\}\);/s', $result, $matches));
    }
}
