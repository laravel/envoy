<?php

namespace Laravel\Envoy;

use Closure;

class TaskContainer
{
    /**
     * All of the registered servers.
     *
     * @var array
     */
    protected $servers = [];

    /**
     * All of the registered macros.
     *
     * @var array
     */
    protected $macros = [];

    /**
     * All of the evaluated tasks.
     *
     * @var array
     */
    protected $tasks = [];

    /**
     * All of the "error" callbacks.
     *
     * @var array
     */
    protected $error = [];

    /**
     * All of the "after" callbacks.
     *
     * @var array
     */
    protected $after = [];

    /**
     * All of the options for each task.
     *
     * @var array
     */
    protected $taskOptions = [];

    /**
     * The stack of tasks being rendered.
     *
     * @var array
     */
    protected $taskStack = [];

    /**
     * The stack of macro being rendered.
     *
     * @var array
     */
    protected $macroStack = [];

    /**
     * All of the options for each macro.
     *
     * @var array
     */
    protected $macroOptions = [];

    /**
     * Silently load the Envoy file into the container.
     *
     * No data is needed.
     *
     * @param  string  $__path
     * @param  \Laravel\Envoy\Compiler  $__compiler
     * @return void
     */
    public function loadServers($path, Compiler $compiler)
    {
        return $this->load($path, $compiler, [], true);
    }

    /**
     * Load the Envoy file into the container.
     *
     * @param  string  $__path
     * @param  \Laravel\Envoy\Compiler  $__compiler
     * @param  array  $__data
     * @param  bool  $__serversOnly
     * @return void
     */
    public function load($__path, Compiler $__compiler, array $__data = [], $__serversOnly = false)
    {
        // First we will compiled the "Blade" Envoy file into plain PHP that we'll include
        // into the current scope so it can register tasks in this task container that
        // is also in the current scope. We will extract this other data into scope.
        $this->writeCompiledEnvoyFile(
            $__compiler, $__path, $__serversOnly
        );

        $__container = $this;

        ob_start() && extract($__data);

        // Here we will include the compiled Envoy file so it can register tasks into this
        // container instance. Then we will delete the PHP version of the file because
        // it is no longer needed once we have used it to register in the container.
        include getcwd().'/Envoy.php';

        @unlink(getcwd().'/Envoy.php');

        $this->replaceSubTasks();

        ob_end_clean();
    }

    /**
     * Write the compiled Envoy file to disk.
     *
     * @param  \Laravel\Envoy\Compiler  $compiler
     * @param  string  $path
     * @return void
     */
    protected function writeCompiledEnvoyFile($compiler, $path, $serversOnly)
    {
        file_put_contents(
            getcwd().'/Envoy.php',
            $compiler->compile(file_get_contents($path), $serversOnly)
        );
    }

    /**
     * Replace all of the sub tasks and trim leading spaces.
     *
     * @return void
     */
    protected function replaceSubTasks()
    {
        foreach ($this->tasks as $name => &$script) {
            $callback = function ($m) { return $m[1].$this->tasks[$m[2]]; };

            $script = $this->trimSpaces(
                preg_replace_callback("/(\s*)@run\('(.*)'\)/", $callback, $script)
            );
        }
    }

    /**
     * Register the array of servers with the container.
     *
     * @param  array  $servers
     * @return void
     */
    public function servers(array $servers)
    {
        $this->servers = $servers;
    }

    /**
     * Get the IP address for a server.
     *
     * @param  string  $server
     * @return string|null
     */
    public function getServer($server)
    {
        if (! array_key_exists($server, $this->servers)) {
            throw new \Exception('Server ['.$server.'] is not defined.');
        }

        return array_get($this->servers, $server);
    }

    /**
     * Determine if the container only has one registered server.
     *
     * @return bool
     */
    public function hasOneServer()
    {
        return count($this->servers) == 1;
    }

    /**
     * Get the first registered server IP address.
     *
     * @return string
     */
    public function getFirstServer()
    {
        return head($this->servers);
    }

    /**
     * Getter for macros.
     *
     * @return array
     */
    public function getMacros()
    {
        return $this->macros;
    }

    /**
     * Get the given macro from the container.
     *
     * @param  string  $macro
     * @return array|null
     */
    public function getMacro($macro)
    {
        return array_get($this->macros, $macro);
    }

    /**
     * Get the macro options for the given macro.
     *
     * @param  string  $macro
     * @return array
     */
    public function getMacroOptions($macro)
    {
        return array_get($this->macroOptions, $macro, []);
    }

    /**
     * Getter for tasks.
     *
     * @return array
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * Get a Task instance by the given name.
     *
     * @param  string  $task
     * @param  array  $macroOptions
     * @return string
     */
    public function getTask($task, array $macroOptions = [])
    {
        $script = array_get($this->tasks, $task, '');

        if ($script == '') {
            throw new \Exception(sprintf('Task "%s" is not defined.', $task));
        }

        $options = array_merge($this->getTaskOptions($task), $macroOptions);

        $parallel = array_get($options, 'parallel', false);

        $confirm = array_get($options, 'confirm', null);

        return new Task($this->getServers($options), $options['as'], $script, $parallel, $confirm);
    }

    /**
     * Get the task options for the given task.
     *
     * @param  string  $task
     * @return array
     */
    public function getTaskOptions($task)
    {
        return array_get($this->taskOptions, $task, []);
    }

    /**
     * Get the IP addresses of the servers specified on the options.
     *
     * @param  array  $options
     * @return array
     */
    protected function getServers(array $options)
    {
        if (! array_key_exists('on', $options)) {
            $options['on'] = [];
        }

        return array_flatten(array_map(function ($name) {
            return $this->getServer($name);
        }, (array) $options['on']));
    }

    /**
     * Begin defining a macro.
     *
     * @param  string  $macro
     * @param  array  $options
     * @return void
     */
    public function startMacro($macro, array $options = [])
    {
        ob_start() && $this->macroStack[] = $macro;

        $this->macroOptions[$macro] = $options;
    }

    /**
     * Stop defining a macro.
     *
     * @return void
     */
    public function endMacro()
    {
        $macro = explode(PHP_EOL, $this->trimSpaces(trim(ob_get_clean())));

        $this->macros[array_pop($this->macroStack)] = $macro;
    }

    /**
     * Begin defining a task.
     *
     * @param  string  $task
     * @param  array  $options
     * @return void
     */
    public function startTask($task, array $options = [])
    {
        ob_start() && $this->taskStack[] = $task;

        $this->taskOptions[$task] = $this->mergeDefaultOptions($options);
    }

    /**
     * Merge the option array over the default options.
     *
     * @param  array  $options
     * @return array
     */
    protected function mergeDefaultOptions(array $options)
    {
        return array_merge(['as' => null, 'on' => array_keys($this->servers)], $options);
    }

    /**
     * Stop defining a task.
     *
     * @return void
     */
    public function endTask()
    {
        $this->tasks[array_pop($this->taskStack)] = trim(ob_get_clean());
    }

    /**
     * Register an after-task callback.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function after(Closure $callback)
    {
        $this->after[] = $callback;
    }

    /**
     * Get all of the after-task callbacks.
     *
     * @return array
     */
    public function getAfterCallbacks()
    {
        return $this->after;
    }

    /**
     * Register an error-task callback.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function error(Closure $callback)
    {
        $this->error[] = $callback;
    }

    /**
     * Get all of the error-task callbacks.
     *
     * @return array
     */
    public function getErrorCallbacks()
    {
        return $this->error;
    }

    /**
     * Remove the leading space from the lines of a value.
     *
     * @param  string  $value
     * @return string
     */
    protected function trimSpaces($value)
    {
        return implode(PHP_EOL, array_map('trim', explode(PHP_EOL, $value)));
    }
}
