<?php

namespace Laravel\Envoy\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait Command
{
    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        return (int) $this->fire();
    }

    /**
     * Get an argument from the input.
     *
     * @param  string  $key
     * @return string
     */
    public function argument($key)
    {
        return $this->input->getArgument($key);
    }

    /**
     * Get an option from the input.
     *
     * @param  string  $key
     * @return string
     */
    public function option($key)
    {
        return $this->input->getOption($key);
    }

    /**
     * Ask the user the given question.
     *
     * @param  string  $question
     * @return string
     */
    public function ask($question)
    {
        $question = '<comment>'.$question.'</comment> ';
        return $this->askQuestion($question);
    }

    /**
     * Confirm the operation with the user.
     *
     * @param  string  $task
     * @param  string  $question
     * @return bool
     */
    public function confirmTaskWithUser($task, $question)
    {
        $question = $question === true ? 'Are you sure you want to run the ['.$task.'] task?' : (string) $question;

        $question = '<comment>'.$question.' [y/N]:</comment> ';

        return $this->confirmQuestion($question);
    }

    /**
     * Ask the user the given secret question.
     *
     * @param  string  $question
     * @return string
     */
    public function secret($question)
    {
        $question = '<comment>'.$question.'</comment> ';

        return $this->askQuestion($question, true);
    }


    /**
     * Ask the user a question. This method is a compatibilty layer between Symfony versions.
     * The 'dialog' helper has been deprecated since Symfony 2.5 and is removed from Symfony 3.0 onwards.
     *
     * @param string $questionString
     * @param bool $isSecret
     *
     * @return string
     */
    protected function askQuestion($questionString, $isSecret = false)
    {
        if( $this->getHelperSet()->has('dialog') ){
            if( $isSecret ){
                return $this->getHelperSet()->get('dialog')->ask($this->output, $questionString);
            }
            else{
                return $this->getHelperSet()->get('dialog')->askHiddenResponse($this->output, $questionString, false);
            }
        }
        else{
            $question = new \Symfony\Component\Console\Question\Question($questionString);
            $question->setHidden($isSecret);
            $question->setHiddenFallback(false);

            return $this->getHelperSet()->get('question')->ask($this->input, $this->output, $question);
        }
    }

    /**
     * Ask the user for a confirmation. This method is a compatibilty layer between Symfony versions.
     * The 'dialog' helper has been deprecated since Symfony 2.5 and is removed from Symfony 3.0 onwards.
     *
     * @param string $questionString
     * @param bool $default
     *
     * @return string
     */
    protected function confirmQuestion($questionString, $default = false)
    {
        if( $this->getHelperSet()->has('dialog') ){
            return $this->getHelperSet()->get('dialog')->askConfirmation($this->output, $questionString, false);
        }
        else{
            $question = new \Symfony\Component\Console\Question\ConfirmationQuestion($questionString, $default);
            return $this->getHelperSet()->get('question')->ask($this->input, $this->output, $question);
        }
    }
}
