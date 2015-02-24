@servers(['web' => '192.168.1.1', 'web2' => '192.168.1.2'])

@task('foo', ['on' => 'web'])
    ls -la
@endtask
@task('bar', ['as' => 'baz', 'on' => 'web2'])
    ls -la
@endtask

@task('doe')
ls -la
@endtask