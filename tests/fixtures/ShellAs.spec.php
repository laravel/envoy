<?php

return [
    'test_as' => function ($output) {
        $this->assertEquals([
            '[127.0.0.1]: root'
        ], $output);
    },
    'test_shell_sh' => function ($output) {
        $this->assertEquals([
            '[127.0.0.1]: sh'
        ], $output);
    },
    'test_shell_bash' => function ($output) {
        $this->assertEquals([
            '[127.0.0.1]: bash'
        ], $output);
    },
    'test_as_shell' => function ($output) {
        $this->assertEquals([
            '[127.0.0.1]: root',
            '[127.0.0.1]: bash'
        ], $output);
    },
];
