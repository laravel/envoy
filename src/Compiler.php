<?php

namespace Laravel\Envoy;

class Compiler
{
    /**
     * All of the available compiler functions.
     *
     * @var array
     */
    protected $serverCompilers = [
        'Servers',
    ];

    /**
     * All of the available compiler functions.
     *
     * @var array
     */
    protected $compilers = [
        'Imports',
        'Sets',
        'Comments',
        'Echos',
        'Openings',
        'Closings',
        'Else',
        'Unless',
        'EndUnless',
        'SetupStart',
        'SetupStop',
        'Include',
        'Servers',
        'MacroStart',
        'MacroStop',
        'TaskStart',
        'TaskStop',
        'Before',
        'BeforeStop',
        'After',
        'AfterStop',
        'Finished',
        'FinishedStop',
        'Success',
        'SuccessStop',
        'Error',
        'ErrorStop',
        'Slack',
        'Discord',
        'Telegram',
        'MicrosoftTeams',
    ];

    /**
     * Array of opening and closing tags for echos.
     *
     * @var array
     */
    protected $contentTags = ['{{', '}}'];

    /**
     * Compile the given Envoy template contents.
     *
     * @param  string  $value
     * @param  bool  $serversOnly
     * @return string
     */
    public function compile($value, $serversOnly = false)
    {
        $compilers = $serversOnly ? $this->serverCompilers : $this->compilers;

        $value = $this->initializeVariables($value);

        foreach ($compilers as $compiler) {
            $value = $this->{"compile{$compiler}"}($value);
        }

        return $value;
    }

    /**
     * Compile Envoy sets into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileSets($value)
    {
        return preg_replace('/\\@set\(\'(.*?)\'\,\s*(.*)\)/', '<?php $$1 = $2; ?>', $value);
    }

    /**
     * Compile Envoy imports into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileImports($value)
    {
        $pattern = $this->createOpenMatcher('import');

        return preg_replace($pattern, '$1<?php $__container->import$2, get_defined_vars()); ?>', $value);
    }

    /**
     * Compile Envoy comments into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileComments($value)
    {
        $pattern = sprintf('/%s--((.|\s)*?)--%s/', $this->contentTags[0], $this->contentTags[1]);

        return preg_replace($pattern, '<?php /*$1*/ ?>', $value);
    }

    /**
     * Compile Envoy echos into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileEchos($value)
    {
        return $this->compileRegularEchos($value);
    }

    /**
     * Compile the "regular" echo statements.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileRegularEchos($value)
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', '{{', '}}');

        $callback = function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3].$matches[3];

            $wrapped = sprintf('%s', $this->compileEchoDefaults($matches[2]));

            return $matches[1] ? substr($matches[0], 1) : '<?php echo '.$wrapped.'; ?>'.$whitespace;
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * Compile the default values for the echo statement.
     *
     * @param  string  $value
     * @return string
     */
    public function compileEchoDefaults($value)
    {
        return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $value);
    }

    /**
     * Compile Envoy structure openings into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileOpenings($value)
    {
        $pattern = '/(?(R)\((?:[^\(\)]|(?R))*\)|(?<!\w)(\s*)@(if|elseif|foreach|for|while)(\s*(?R)+))/';

        return preg_replace($pattern, '$1<?php $2$3: ?>', $value);
    }

    /**
     * Compile Envoy structure closings into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileClosings($value)
    {
        $pattern = '/(\s*)@(endif|endforeach|endfor|endwhile)(\s*)/';

        return preg_replace($pattern, '$1<?php $2; ?>$3', $value);
    }

    /**
     * Compile Envoy else statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileElse($value)
    {
        $pattern = $this->createPlainMatcher('else');

        return preg_replace($pattern, '$1<?php else: ?>$2', $value);
    }

    /**
     * Compile Envoy unless statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileUnless($value)
    {
        $pattern = $this->createMatcher('unless');

        return preg_replace($pattern, '$1<?php if ( !$2): ?>', $value);
    }

    /**
     * Compile Envoy end unless statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileEndUnless($value)
    {
        $pattern = $this->createPlainMatcher('endunless');

        return preg_replace($pattern, '$1<?php endif; ?>$2', $value);
    }

    /**
     * Compile setup section begin statement into PHP start tag.
     *
     * @param  string  $value
     * @return string
     */
    public function compileSetupStart($value)
    {
        $value = preg_replace('/(\s*)@setup(\s*)/', '$1<?php$2', $value);

        return preg_replace('/(\s*)@php(\s*)/', '$1<?php$2', $value);
    }

    /**
     * Compile setup section stop statement into PHP end tag.
     *
     * @param  string  $value
     * @return string
     */
    public function compileSetupStop($value)
    {
        $value = preg_replace('/(\s*)@endsetup(\s*)/', '$1?>$2', $value);

        return preg_replace('/(\s*)@endphp(\s*)/', '$1?>$2', $value);
    }

    /**
     * Compile an @include into a PHP include statement.
     *
     * @param  string  $value
     * @return string
     */
    public function compileInclude($value)
    {
        $pattern = $this->createMatcher('include');

        return preg_replace($pattern, '$1 <?php require_once$2; ?>', $value);
    }

    /**
     * Compile Envoy server statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileServers($value)
    {
        $pattern = $this->createMatcher('servers');

        return preg_replace($pattern, '$1<?php $__container->servers$2; ?>', $value);
    }

    /**
     * Compile Envoy macro start statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileMacroStart($value)
    {
        $pattern = $this->createMatcher('macro');

        $value = preg_replace($pattern, '$1<?php $__container->startMacro$2; ?>', $value);

        $pattern = $this->createMatcher('story');

        return preg_replace($pattern, '$1<?php $__container->startMacro$2; ?>', $value);
    }

    /**
     * Compile Envoy macro stop statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileMacroStop($value)
    {
        $pattern = $this->createPlainMatcher('endmacro');

        $value = preg_replace($pattern, '$1<?php $__container->endMacro(); ?>$2', $value);

        $pattern = $this->createPlainMatcher('endstory');

        return preg_replace($pattern, '$1<?php $__container->endMacro(); ?>$2', $value);
    }

    /**
     * Compile Envoy task start statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileTaskStart($value)
    {
        $pattern = $this->createMatcher('task');

        return preg_replace($pattern, '$1<?php $__container->startTask$2; ?>', $value);
    }

    /**
     * Compile Envoy task stop statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileTaskStop($value)
    {
        $pattern = $this->createPlainMatcher('endtask');

        return preg_replace($pattern, '$1<?php $__container->endTask(); ?>$2', $value);
    }

    /**
     * Compile Envoy before statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileBefore($value)
    {
        $pattern = $this->createPlainMatcher('before');

        return preg_replace($pattern, '$1<?php $_vars = get_defined_vars(); $__container->before(function($task) use ($_vars) { extract($_vars, EXTR_SKIP)  ; $2', $value);
    }

    /**
     * Compile Envoy before stop statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileBeforeStop($value)
    {
        return preg_replace($this->createPlainMatcher('endbefore'), '$1}); ?>$2', $value);
    }

    /**
     * Compile Envoy after statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileAfter($value)
    {
        $pattern = $this->createPlainMatcher('after');

        return preg_replace($pattern, '$1<?php $_vars = get_defined_vars(); $__container->after(function($task) use ($_vars) { extract($_vars, EXTR_SKIP)  ; $2', $value);
    }

    /**
     * Compile Envoy after stop statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileAfterStop($value)
    {
        return preg_replace($this->createPlainMatcher('endafter'), '$1}); ?>$2', $value);
    }

    /**
     * Compile Envoy finished statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileFinished($value)
    {
        $pattern = $this->createPlainMatcher('finished');

        return preg_replace($pattern, '$1<?php $_vars = get_defined_vars(); $__container->finished(function($exitCode = null) use ($_vars) { extract($_vars); $2', $value);
    }

    /**
     * Compile Envoy finished stop statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileFinishedStop($value)
    {
        return preg_replace($this->createPlainMatcher('endfinished'), '$1}); ?>$2', $value);
    }

    /**
     * Compile Envoy success statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileSuccess($value)
    {
        $pattern = $this->createPlainMatcher('success');

        return preg_replace($pattern, '$1<?php $_vars = get_defined_vars(); $__container->success(function() use ($_vars) { extract($_vars); $2', $value);
    }

    /**
     * Compile Envoy success stop statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileSuccessStop($value)
    {
        return preg_replace($this->createPlainMatcher('endsuccess'), '$1}); ?>$2', $value);
    }

    /**
     * Compile Envoy error statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileError($value)
    {
        $pattern = $this->createPlainMatcher('error');

        return preg_replace($pattern, '$1<?php $_vars = get_defined_vars(); $__container->error(function($task) use ($_vars) { extract($_vars, EXTR_SKIP); $2', $value);
    }

    /**
     * Compile Envoy error stop statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileErrorStop($value)
    {
        return preg_replace($this->createPlainMatcher('enderror'), '$1}); ?>$2', $value);
    }

    /**
     * Compile Envoy Slack statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileSlack($value)
    {
        $pattern = $this->createMatcher('slack');

        return preg_replace($pattern, '$1 if (! isset($task)) $task = null; Laravel\Envoy\Slack::make$2->task($task)->send();', $value);
    }

    /**
     * Compile Envoy Discord statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileDiscord($value)
    {
        $pattern = $this->createMatcher('discord');

        return preg_replace($pattern, '$1 if (! isset($task)) $task = null; Laravel\Envoy\Discord::make$2->task($task)->send();', $value);
    }

    /**
     * Compile Envoy Telegram statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileTelegram($value)
    {
        $pattern = $this->createMatcher('telegram');

        return preg_replace($pattern, '$1 if (! isset($task)) $task = null; Laravel\Envoy\Telegram::make$2->task($task)->send();', $value);
    }

    /**
     * Compile Envoy Teams statements into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileMicrosoftTeams($value)
    {
        $pattern = $this->createMatcher('microsoftTeams');

        return preg_replace($pattern, '$1 if (! isset($task)) $task = null; Laravel\Envoy\MicrosoftTeams::make$2->task($task)->send();', $value);
    }

    /**
     * Initialize the variables included in the Envoy template.
     *
     * @param  string  $value
     * @return string
     */
    private function initializeVariables($value)
    {
        preg_match_all('/\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/', $value, $matches);

        foreach (array_unique($matches[0]) as $variable) {
            $value = "<?php $variable = isset($variable) ? $variable : null; ?>\n".$value;
        }

        return $value;
    }

    /**
     * Get the regular expression for a generic Envoy function.
     *
     * @param  string  $function
     * @return string
     */
    public function createMatcher($function)
    {
        return '/(?<!\w)(\s*)@'.$function.'(\s*\(.*\))/';
    }

    /**
     * Get the regular expression for a generic Envoy function.
     *
     * @param  string  $function
     * @return string
     */
    public function createOpenMatcher($function)
    {
        return '/(?<!\w)(\s*)@'.$function.'(\s*\(.*)\)/';
    }

    /**
     * Create a plain Envoy matcher.
     *
     * @param  string  $function
     * @return string
     */
    public function createPlainMatcher($function)
    {
        return '/(?<!\w)(\s*)@'.$function.'(\s*)/';
    }
}
