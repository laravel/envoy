@servers(['local' => '127.0.0.1'])

@task('test_as', ['as' => 'root'])
whoami
@endtask

@task('test_shell_bash', ['shell' => '/bin/bash'])
ps -p $$ | tail -n1 | awk '{ print $4; }'
@endtask

@task('test_shell_sh', ['shell' => '/bin/sh'])
ps -p $$ | tail -n1 | awk '{ print $4; }'
@endtask

@task('test_as_shell', ['as' => 'root', 'shell' => '/bin/bash'])
whoami
ps -p $$ | tail -n1 | awk '{ print $4; }'
@endtask
