# Laravel Envoy

Elegant SSH tasks for PHP.

- [Installation](#installation)
- [Running Tasks](#running-tasks)
- [Passing Variables](#passing-variables)
- [Macros](#macros)
- [Multiple Servers](#multiple-servers)
- [Parallel Execution](#parallel-execution)
- [Notifications (Hipchat, Slack, Flowdock)](#notifications)

<a name="what-is-it"></a>
## What Is It?

Envoy is a simple SSH task runner for PHP 5.4 or greater. It is loosely inspired by Python Fabric. It supports:

- Clean, minimal syntax for defining tasks.
- Utilizes ~/.ssh/config settings.
- Parallel execution across multiple servers.
- Stops execution if any command fails.
- Macros quickly group tasks into a single command.
- HipChat, Slack and Flowdock notifications.

Envoy is perfect for automating common tasks you perform on your remote servers such as deployment, restarting queues, etc.

<a name="installation"></a>
## Installation

The simplest method of installation is to install it as a global Composer package:

    composer global require "laravel/envoy=~1.0"

Once the package has been installed, you should add the global Composer bin directory to your PATH. On Mac / Linux systems, this directory is `~/.composer/vendor/bin`.

### Updating Envoy

To update Envoy, you may use the `composer global update` command.

<a name="running-tasks"></a>
## Running Tasks

Create an `Envoy.blade.php` file in any directory. Here is a sample file to get you started:

```
@servers(['web' => 'root@192.168.1.1'])

@task('foo', ['on' => 'web'])
	ls -la
@endtask
```

You may also use the `init` command to create a stub Envoy file. For example:

	envoy init user@192.168.1.1

You may define multiple tasks in a given file. To run a task, use the `run` command:

	envoy run foo

> **Note:** For best results, your machine should have SSH key access to the target.

<a name="passing-variables"></a>
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

<a name="multiple-servers"></a>
## Multiple Servers

```
@servers(['web' => 'root@192.168.1.1', 'db' => 'root@192.168.1.2'])

@task('foo', ['on' => ['web', 'db']])
	ls -la
@endtask
```

> **Note:** Tasks on multiple servers will be run serially by default.

<a name="parallel-execution"></a>
## Parallel Execution

To run a task across multiple servers in parallel, use the `parallel` option on the task:

```
@servers(['web' => 'root@192.168.1.1', 'db' => 'root@192.168.1.2'])

@task('foo', ['on' => ['web', 'db'], 'parallel' => true])
	ls -la
@endtask
```

<a name="notifications"></a>
## Notifications
- [HipChat Notifications](#hipchat-notifications)
- [Slack Notifications](#slack-notifications)
- [Flowdock Notifications](#flowdock-notifications)

> **Note:** Notifications will only be sent if all tasks complete successfully.
>
> Message default to: &lt;SYSTEM USER&gt; ran the &lt;TASK NAME&gt; task.

<a name="hipchat-notifications"></a>
### HipChat Notifications

```
@servers(['web' => '192.168.1.1'])

@task('foo', ['on' => 'web'])
	ls -la
@endtask

@after
	@hipchat('token', 'room', 'from', 'message', 'color')
@endafter
```

color default to purple

<a name="slack-notifications"></a>
### Slack Notifications

```
@servers(['web' => '192.168.1.1'])

@task('foo', ['on' => 'web'])
	ls -la
@endtask

@after
	@slack('hook', 'channel')
@endafter
```

You may retrieve your token by creating an **Incoming WebHooks** integration on Slack's website.

The hook argument is the entire webhook URL provided by the Incoming Webhooks Slack Integration:

- `https://hooks.slack.com/services/ZZZZZZZZZ/YYYYYYYYY/XXXXXXXXXXXXXXX`

You may provide one of the following for the channel argument:

- For a regular channel: `#channel`
- For a specific user: `@user`
- If no argument is provided Envoy will use the default channel configured on the Slack website.

<a name="flowdock-notifications"></a>
### Flowdock Notifications

```
@servers(['web' => '192.168.1.1'])

@task('foo', ['on' => 'web'])
	ls -la
@endtask

@after
	@flowdock('token', 'source', 'fromAddress', 'tags', 'link', 'message', 'subject')
@endafter
```

To obtain the token, go to Settings -> Team Inbox inside a flow or open the [Tokens page](https://www.flowdock.com/account/tokens) for all your tokens.

The source and fromAddress arguments are required.

The subject argument default to same as message.

The tags argument (optional) is the tags of the message, separated by commas. White spaced ones would be ignored. Example value: 'cool,stuff,#Envoy,@all,@user'
