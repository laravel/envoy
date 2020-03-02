# Upgrade Guide

## Upgrading To 2.0 From 1.x

### Minimum Laravel Version

Laravel 6.0 is now the minimum supported version of the framework.

### Minimum PHP Version

PHP 7.2 is now the minimum supported version of the language.

### Task Options Now Overwrite Story Options

PR: https://github.com/laravel/envoy/pull/157

Previously, if you defined the same options on tasks and stories, story options would take precedent. Now, you have the ability to overwrite story options on individual tasks. This can be handy if you, for example, want t run some tasks only on specific servers.
