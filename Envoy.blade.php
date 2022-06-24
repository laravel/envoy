@servers(['web' => ['127.0.0.1']])

@story('baz')
    bar
    foo
@endstory

@localtask('bar')
    ls -la
@endlocaltask

@task('foo', ['on' => 'web'])
    ls -la
@endtask
