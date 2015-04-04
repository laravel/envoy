@servers(['web' => 'laravel'])

@task('foo', ['on' => 'web'])
    ls -la
@endtask

@task('confirmed', ['on' => 'web', 'confirm' => true])
    ls -la
@endtask

@after
    @hipchat('token', 'room', 'Envoy')
@endafter
