# Laravel Envoy

Elegant SSH tasks for PHP.

- [Installation](#installation)
- [Running Tasks](#running-tasks)
- [Passing Variables](#passing-variables)
- [Multiple Servers](#multiple-servers)
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

>> **Note:** For best results, your machine should have SSH key access to the target.

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

<a name="multiple-servers">
### Multiple Servers

```
@servers(['web' => 'root@192.168.1.1', 'db' => 'root@192.168.1.2'])

@task('foo', ['on' => ['web', 'db']])
	ls -la
@endtask
```

> **Note:** Tasks on multiple servers will be run serially.

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