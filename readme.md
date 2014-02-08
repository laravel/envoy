# Laravel Envoy

Elegant SSH tasks for PHP.

## Installation

Envoy is a simple SSH task runner for PHP, and requires PHP 5.4 or greater. To compile the `envoy.phar` file yourself, clone this repository and run the `box build` command. To run `box` commands, you must install [https://github.com/kherge/box](kherge/Box).

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

You may also pass variables into the task:

	envoy run foo --branch=master

```
@task('foo')
	cd site
	git pull origin {{ $branch }}
	php artisan migrate
@endtask
```