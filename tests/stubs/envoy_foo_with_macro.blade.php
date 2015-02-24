@servers(['web' => '192.168.1.1'])

@macro('foo')
    foo-task
@endmacro

@macro('foo-task')
    foo-task
@endmacro

@task('foo-task', ['on' => 'web'])
ls -la
@endtask
