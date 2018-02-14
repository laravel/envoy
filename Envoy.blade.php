@servers(['web' => ['127.0.0.1']])

@task('foo', ['on' => 'web'])
    ls -la
@endtask
