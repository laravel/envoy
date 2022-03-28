<?php

return [
    'story_1' => function ($output, $code) {
        $this->assertEquals(0, $code);
        $this->assertEquals([
            '[127.0.0.1]: 1',
            '[127.0.0.1]: 3',
            'finished',
        ], $output);
    },
    'story_2' => function ($output, $code) {
        $this->assertEquals(0, $code);
        $this->assertEquals([
            '[127.0.0.1]: 2',
            '[127.0.0.1]: 3',
            'finished',
        ], $output);
    },
];
