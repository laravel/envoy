<?php
namespace Laravel\Envoy\Tests;

use Laravel\Envoy\Compiler;
use Laravel\Envoy\TaskContainer;
use PHPUnit_Framework_TestCase;

class TaskContainerTest extends PHPUnit_Framework_Testcase
{
    /**
     * @var $taskContainer TaskContainer
     */
    private $taskContainer;


    public function setUp()
    {
        $this->taskContainer = new TaskContainer;
    }

    public function tearDown()
    {
        unset($this->taskContainer);
    }

    /**
     * @test
     * @dataProvider macrosProvider
     */
    public function it_should_read_macros($path, $hasFooMacro)
    {
        $this->taskContainer->load($path, new Compiler(), []);
        if ($hasFooMacro) {
            $this->assertMacro($this->taskContainer->getMacro('foo'));
        } else {
            $this->assertNull($this->taskContainer->getMacro('foo'));
        }
    }

    /**
     * @test
     * @expectedException \Exception
     */
    public function it_should_throw_exception_if_task_doesnt_exists()
    {
        $this->taskContainer->load(__DIR__ . '/stubs/envoy_foo.blade.php', new Compiler(), []);

        $this->taskContainer->getTask('baz');
    }

    /**
     * @test
     */
    public function it_should_read_task()
    {
        $this->taskContainer->load(__DIR__ . '/stubs/envoy_foo.blade.php', new Compiler(), []);

        $this->assertTask($this->taskContainer->getTask('foo'));
    }

    /**
     * @test
     * @dataProvider onlyOneServerProvider
     */
    public function it_shoulds_check_if_file_has_one_server($file, $hasOneServer)
    {
        $this->taskContainer->load($file, new Compiler(), []);
        $this->assertSame($hasOneServer, $this->taskContainer->hasOneServer());
    }

    /**
     * @test
     * @dataProvider serversProvider
     */
    public function it_should_return_the_servers($file, $serverInfos)
    {
        $this->taskContainer->load($file, new Compiler(), []);
        foreach ($serverInfos as $name => $host) {
            $this->assertSame($host, $this->taskContainer->getServer($name));
        }
    }

    /**
     * @test
     * @expectedException \Exception
     */
    public function it_should_throw_an_exception_if_server_doesnt_exist()
    {
        $this->taskContainer->load(__DIR__ .'/stubs/envoy_foo_with_macro.blade.php', new Compiler(), []);

        $this->taskContainer->getServer('foo');
    }

    /**
     * @test
     */
    public function it_should_return_the_tasks_options()
    {
        $this->taskContainer->load(__DIR__ .'/stubs/envoy_foo.blade.php', new Compiler(), []);

        $this->assertEquals(['as' => null, 'on' => 'web'], $this->taskContainer->getTaskOptions('foo'));
        $this->assertEquals(['as' => 'baz', 'on' => 'web2'], $this->taskContainer->getTaskOptions('bar'));
    }

    /**
     * @test
     */
    public function it_should_execute_on_all_servers_if_no_server_is_specified()
    {
        $this->taskContainer->load(__DIR__ .'/stubs/envoy_foo.blade.php', new Compiler(), []);

        $taskOptions = $this->taskContainer->getTaskOptions('doe');

        $this->assertContains('web', $taskOptions['on']);
        $this->assertContains('web2', $taskOptions['on']);
    }

    /**
     * @test
     * @dataProvider afterProviders
     */
    public function it_should_return_afters($file, $nb)
    {
        $this->taskContainer->load($file, new Compiler(), []);
        $callbacks = $this->taskContainer->getAfterCallbacks();

        $this->assertCount($nb, $callbacks);
    }

    public function afterProviders()
    {
        return [
            'no_after' => [
                __DIR__ . '/stubs/envoy_no_server.blade.php',
                0,
            ],
            'after' => [
                __DIR__ . '/stubs/envoy_foo_with_after.blade.php',
                1,
            ]
        ];
    }

    public function onlyOneServerProvider()
    {
        return [
            'no_server' => [
                __DIR__ . '/stubs/envoy_no_server.blade.php',
                false,
            ],
            '1 server' => [
                __DIR__ . '/stubs/envoy_foo_with_macro.blade.php',
                true,
            ],
            '2 server' => [
                __DIR__ . '/stubs/envoy_foo.blade.php',
                false,
            ]
        ];
    }

    public function serversProvider()
    {
        return [
            '1 server' => [
                __DIR__ . '/stubs/envoy_foo_with_macro.blade.php',
                ['web' => '192.168.1.1']
            ],
            '2 server' => [
                __DIR__ . '/stubs/envoy_foo.blade.php',
                ['web' => '192.168.1.1', 'web2' => '192.168.1.2']
            ]
        ];

    }

    public function macrosProvider()
    {
        return [
            'no_macro' => [
                __DIR__ . '/stubs/envoy_foo.blade.php',
                false
            ],
            'foo_macro' => [
                __DIR__ . '/stubs/envoy_foo_with_macro.blade.php',
                true
            ]
        ];
    }

    private function assertMacro($value)
    {
        $this->assertInternalType('array', $value, 'The given value is not a Macro');
    }

    private function assertTask($value)
    {
        $this->assertInstanceOf('\Laravel\Envoy\Task', $value, 'The given value is not a Task');
    }
}