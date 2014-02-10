# Laravel Envoy

Elegant SSH tasks for PHP.

- [Installation](#installation)
- [Running Tasks](#running-tasks)
- [Passing Variables](#passing-variables)
- [Macros](#macros)
- [Multiple Servers](#multiple-servers)
- [Parallel Execution](#parallel-execution)
- [HipChat Notifications](#hipchat-notifications)

<a name="installation">
## Installation

Envoy is a simple SSH task runner for PHP 5.4 or greater. To compile the `envoy.phar` file yourself, clone this repository and run the `box build` command. To run `box` commands, you must install [kherge/Box](https://github.com/kherge/Box).

Once the Phar has been compiled, move it to `/usr/local/bin` as `envoy` for easy access.

<a name="running-tasks">
## Running Tasks

Create an `Envoy.blade.php` file in any directory. Here is a sample file to get you started:

```
@servers(['web' => 'root@192.168.1.1'])

@task('foo', ['on' => 'web'])
	ls -la
@endtask
```

You may define multiple tasks in a given file. To run a task, use the `run` command:

	envoy run foo

> **Note:** For best results, your machine should have SSH key access to the target.

<a name="passing-variables">
### Passing Variables

```
envoy run foo --branch=master
```

```
@task('foo')
	cd site
	git pull origin {{ $branch }}
	php artisan migrate
@endtask
```

<a name="macros"></a>
### Macros

Macros allow you to define a set of tasks to run in sequence using a single command. For instance:

```
@macro('deploy')
	foo
	bar
@endmacro

@task('foo')
	echo "HELLO"
@endtask

@task('bar')
	echo "WORLD"
@endtask
```

```
envoy run deploy
```

<a name="multiple-servers">
## Multiple Servers

```
@servers(['web' => 'root@192.168.1.1', 'db' => 'root@192.168.1.2'])

@task('foo', ['on' => ['web', 'db']])
	ls -la
@endtask
```

> **Note:** Tasks on multiple servers will be run serially by default.

<a name="parallel-execution">
## Parallel Execution

To run a task across multiple servers in parallel, use the `parallel` option on the task:

@servers(['web' => 'root@192.168.1.1', 'db' => 'root@192.168.1.2'])

@task('foo', ['on' => ['web', 'db'], 'parallel' => true])
	ls -la
@endtask

<a name="hipchat-notifications">
## HipChat Notifications

```
@servers(['web' => '192.168.1.1'])

@task('foo', ['on' => 'web'])
	ls -la
@endtask

@after
	@hipchat('token', 'room', 'from')
@endafter
```

> **Note:** HipChat notifications will only be sent if all tasks complete successfully.