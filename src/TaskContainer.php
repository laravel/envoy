<?php

namespace Laravel\Envoy;

use Closure;
use Exception;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class TaskContainer
{
    /**
     * All of the registered servers.
     *
     * @var array
     */
    protected $servers = [];

    /**
     * All of the shared data.
     *
     * @var array
     */
    protected $sharedData = [];

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
     * All of the "success" callbacks.
     *
     * @var array
     */
    protected $success = [];

    /**
     * All of the "error" callbacks.
     *
     * @var array
     */
    protected $error = [];

    /**
     * All of the "before" callbacks.
     *
     * @var array
     */
    protected $before = [];

    /**
     * All of the "after" callbacks.
     *
     * @var array
     */
    protected $after = [];

    /**
     * All of the "finished" callbacks.
     *
     * @var array
     */
    protected $finished = [];

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
     * @param  string  $path
     * @param  \Laravel\Envoy\Compiler  $compiler
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
        $__dir = realpath(dirname($__path));

        // First we will compiled the "Blade" Envoy file into plain PHP that we'll include
        // into the current scope so it can register tasks in this task container that
        // is also in the current scope. We will extract this other data into scope.
        $__envoyPath = $this->writeCompiledEnvoyFile(
            $__compiler, $__path, $__serversOnly
        );

        $__container = $this;

        ob_start() && extract($__data);

        // Here we will include the compiled Envoy file so it can register tasks into this
        // container instance. Then we will delete the PHP version of the file because
        // it is no longer needed once we have used it to register in the container.
        include $__envoyPath;

        @unlink($__envoyPath);

        $this->replaceSubTasks();

        ob_end_clean();
    }

    /**
     * Write the compiled Envoy file to disk.
     *
     * @param  \Laravel\Envoy\Compiler  $compiler
     * @param  string  $path
     * @param  bool  $serversOnly
     * @return string
     */
    protected function writeCompiledEnvoyFile($compiler, $path, $serversOnly)
    {
        file_put_contents(
            $envoyPath = getcwd().'/Envoy'.md5_file($path).'.php',
            $compiler->compile(file_get_contents($path), $serversOnly)
        );

        return $envoyPath;
    }

    /**
     * Replace all of the sub tasks and trim leading spaces.
     *
     * @return void
     */
    protected function replaceSubTasks()
    {
        foreach ($this->tasks as $name => &$script) {
            $callback = function ($m) {
                return $m[1].$this->tasks[$m[2]];
            };

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
     *
     * @throws \Exception
     */
    public function getServer($server)
    {
        if (! array_key_exists($server, $this->servers)) {
            throw new Exception('Server ['.$server.'] is not defined.');
        }

        return Arr::get($this->servers, $server);
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
     * Import the given file into the container.
     *
     * @param  string  $file
     * @param  array  $data
     * @return void
     */
    public function import($file, array $data = [])
    {
        $data = Arr::except($data, [
            '__path', '__dir', '__compiler', '__data', '__serversOnly',
            '__envoyPath', '__container', 'this',
        ]);

        if (($path = $this->resolveImportPath($file)) === false) {
            throw new InvalidArgumentException("Unable to locate file: [{$file}].");
        }

        $this->load($path, new Compiler, $data);
    }

    /**
     * Resolve the import path for the given file.
     *
     * @param  string  $file
     * @return string|bool
     */
    protected function resolveImportPath($file)
    {
        if (($path = realpath($file)) !== false) {
            return $path;
        } elseif (($path = realpath($file.'.blade.php')) !== false) {
            return $path;
        } elseif (($path = realpath(getcwd().'/vendor/'.$file.'/Envoy.blade.php')) !== false) {
            return $path;
        } elseif (($path = realpath(__DIR__.'/'.$file.'.blade.php')) !== false) {
            return $path;
        }

        return false;
    }

    /**
     * Share the given piece of data across all tasks.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function share($key, $value)
    {
        $this->sharedData[$key] = $value;
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
        return Arr::get($this->macros, $macro);
    }

    /**
     * Get the macro options for the given macro.
     *
     * @param  string  $macro
     * @return array
     */
    public function getMacroOptions($macro)
    {
        return Arr::get($this->macroOptions, $macro, []);
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
     * @return \Laravel\Envoy\Task
     *
     * @throws \Exception
     */
    public function getTask($task, array $macroOptions = [])
    {
        $script = Arr::get($this->tasks, $task, '');

        if ($script == '') {
            throw new Exception(sprintf('Task "%s" is not defined.', $task));
        }

        $options = array_merge($this->getTaskOptions($task), $macroOptions);

        $parallel = Arr::get($options, 'parallel', false);

        $confirm = Arr::get($options, 'confirm', null);

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
        return Arr::get($this->taskOptions, $task, []);
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

        return Arr::flatten(array_map(function ($name) {
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
        $macro = array_map('trim', preg_split('/\n|\r\n?/', $this->trimSpaces(trim(ob_get_clean()))));

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
        $name = array_pop($this->taskStack);

        $contents = trim(ob_get_clean());

        if (isset($this->tasks[$name])) {
            $this->tasks[$name] = str_replace('@parent', $this->tasks[$name], $contents);
        } else {
            $this->tasks[$name] = $contents;
        }
    }

    /**
     * Register a before-task callback.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function before(Closure $callback)
    {
        $this->before[] = $callback;
    }

    /**
     * Get all of the before-task callbacks.
     *
     * @return array
     */
    public function getBeforeCallbacks()
    {
        return $this->before;
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
     * Register an finished-task callback.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function finished(Closure $callback)
    {
        $this->finished[] = $callback;
    }

    /**
     * Get all of the finished-task callbacks.
     *
     * @return array
     */
    public function getFinishedCallbacks()
    {
        return $this->finished;
    }

    /**
     * Register an success-task callback.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function success(Closure $callback)
    {
        $this->success[] = $callback;
    }

    /**
     * Get all of the success-task callbacks.
     *
     * @return array
     */
    public function getSuccessCallbacks()
    {
        return $this->success;
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
