@servers(['prod' => 'engine', 'dev' => 'engine-dev'])

@task('foo', ['on' => ['engine', 'engine-dev']])
	ls -la
@endtask

@after
	//
@endafter