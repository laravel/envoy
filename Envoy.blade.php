@servers(['web' => '192.168.1.1'])

@task('foo', ['on' => 'web'])
    ls -la
@endtask

@task('confirmed', ['on' => 'web', 'confirm' => true])
    ls -la
@endtask

@after
    @hipchat('token', 'room', 'Envoy')
@endafter
