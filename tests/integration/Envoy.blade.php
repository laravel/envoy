
@servers(['local' => ['localhost']])

@task('test_commmand_line_option_with_dashes', ['on' => 'local'])
    if [ '{{ isset(${'command-line-option-with-dashes'}) ? ${'command-line-option-with-dashes'} : '' }}' != 'foobar' ]
    then
        exit 1
    fi
@endtask
