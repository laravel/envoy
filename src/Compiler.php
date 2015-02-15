<?php namespace Laravel\Envoy;

class Compiler {

	/**
	 * All of the available compiler functions.
	 *
	 * @var array
	 */
	protected $serverCompilers = array(
		'Servers',
	);

	/**
	 * All of the available compiler functions.
	 *
	 * @var array
	 */
	protected $compilers = array(
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
		'After',
		'AfterStop',
		'Error',
		'ErrorStop',
		'Hipchat',
		'Slack',
		'Flowdock',
	);

	/**
	 * Array of opening and closing tags for echos.
	 *
	 * @var array
	 */
	protected $contentTags = array('{{', '}}');

	/**
	 * Compile the given Envoy template contents.
	 *
	 * @param  string  $value
	 * @param  bool  $silent
	 * @return string
	 */
	public function compile($value, $serversOnly = false)
	{
		$compilers = $serversOnly ? $this->serverCompilers : $this->compilers;

		foreach ($compilers as $compiler)
		{
			$value = $this->{"compile{$compiler}"}($value);
		}

		return $value;
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
		$callback = function($matches)
		{
			return $matches[1] ? substr($matches[0], 1) : '<?php echo '.$this->compileEchoDefaults($matches[2]).'; ?>';
		};

		return preg_replace_callback('/(@)?{{\s*(.+?)\s*}}/s', $callback, $this->compileEndOfLineEchos($value));
	}

	/**
	 * Compile the echo statements that are on end of lines.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileEndOfLineEchos($value)
	{
		$callback = function($matches)
		{
			return $matches[1] ? substr($matches[0], 1) : '<?php echo '.$this->compileEchoDefaults($matches[2]).'; ?>'.PHP_EOL;
		};

		return preg_replace_callback('/(@)?{{\s*(.+?)\s*}}\s*$/s', $callback, $value);
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
	* @param  string   $value
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
	 * @param  string $value
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
	 * @param  string $value
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
	 * Compile Envoy after statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileAfter($value)
	{
		$pattern = $this->createPlainMatcher('after');

		return preg_replace($pattern, '$1<?php $_vars = get_defined_vars(); $__container->after(function($task) use ($_vars) { extract($_vars); $2', $value);
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
	 * Compile Envoy error statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileError($value)
	{
		$pattern = $this->createPlainMatcher('error');

		return preg_replace($pattern, '$1<?php $__container->error(function($task) {$2', $value);
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
	 * Compile Envoy HipChat statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileHipchat($value)
	{
		$pattern = $this->createMatcher('hipchat');

		return preg_replace($pattern, '$1 Laravel\Envoy\Hipchat::make$2->task($task)->send();', $value);
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

		return preg_replace($pattern, '$1 Laravel\Envoy\Slack::make$2->task($task)->send();', $value);
	}

	/**
	 * Compile Envoy Flowdock statements into valid PHP.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileFlowdock($value)
	{
		$pattern = $this->createMatcher('flowdock');

		return preg_replace($pattern, '$1 Laravel\Envoy\Flowdock::make$2->task($task)->send();', $value);
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
