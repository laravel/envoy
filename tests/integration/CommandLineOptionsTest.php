<?php

namespace Laravel\Envoy;

class CommandLineOptionsTest extends TestCase
{
    public function test_command_line_option_with_dashes()
    {
        $envoy_bin = realpath(__DIR__.'/../../envoy');
        $working_dir = __DIR__;
        $task_name = 'test_commmand_line_option_with_dashes';
        $options = '--command-line-option-with-dashes=foobar';
        
        $output = system("cd $working_dir ; $envoy_bin run $task_name $options", $return_val);

        $this->assertEquals(0, $return_val);
    }
}
