@servers(['local' => '127.0.0.1'])

@story('story_1')
task_1
task_3
@endstory

@story('story_2')
task_2
task_3
@endstory

@task('task_1')
echo 1
@endtask

@task('task_2')
echo 2
@endtask

@task('task_3')
echo 3
@endtask

@finished
echo "finished";
@endfinished
