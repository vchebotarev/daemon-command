<?php

namespace Chebur\DaemonCommand;

use BadFunctionCallException;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractDaemonCommand extends Command
{
    protected function configure()
    {
        $this->addOption('pause', 'p', InputOption::VALUE_OPTIONAL, 'Pause between iterations in seconds', 0);
        $this->addOption('memory', 'm', InputOption::VALUE_OPTIONAL, 'Memory limit in megabytes', -1);
        $this->addOption('time', 't', InputOption::VALUE_OPTIONAL, 'Time limit in seconds');
        $this->addOption('iterations', 'i', InputOption::VALUE_OPTIONAL, 'Iterations limit');
    }

    abstract protected function executeIteration(ExecutionContext $context): void;

    protected function beforeCycle(ExecutionContext $context): void
    {

    }

    protected function afterCycle(ExecutionContext $context): void
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = $this->buildContext($input, $output);
        $this->registerSignals($context);
        $this->beforeCycle($context);
        while(true) {
            $this->executeIteration($context);
            $context->incrementIterations();
            if ($this->checkStop($context)) {
                break;
            }
            $this->pause($context->getPauseBetweenIterations(), $context);
        }
        $this->afterCycle($context);

        $output->writeln('<info>Total execution time:</info> ' . $context->getExecutionTime() . 's');
        if ($context->getTotalPauseTime()) {
            $output->writeln('<info>Total pause time:</info> '. $context->getTotalPauseTime() . 's');
        }
        $output->writeln('<info>Memory usage:</info> '. round(memory_get_usage(true) / 1024 / 1024, 3) . 'MB');
        $output->writeln('<info>Iterations count:</info> '. $context->getIterationsCount());
    }

    private function buildContext(InputInterface $input, OutputInterface $output): ExecutionContext
    {
        $pause = (int)$input->getOption('pause');
        if ($pause < 0) {
            throw new InvalidArgumentException('Pause time option value should be greater than 0');
        }
        $memoryLimit = (int)$input->getOption('memory');
        $memoryLimit = $memoryLimit === -1 ? $memoryLimit : $memoryLimit * 1024 * 1024;
        if ($memoryLimit === 0 || $memoryLimit < -1) {
            throw new InvalidArgumentException('Memory limit option value should be greater than 0 or equal to -1');
        }
        if ($memoryLimit > memory_limit_bytes()) {
            throw new InvalidArgumentException('Memory limit option value should not be greater than "memory_limit" in the php.ini ('.memory_limit().')');
        }

        $timeLimit = $input->getOption('time');
        $timeLimit = $timeLimit === null ? $timeLimit : (int)$timeLimit;
        if ($timeLimit !== null && $timeLimit <= 0) {
            throw new InvalidArgumentException('Time limit option value should be greater than 0');
        }

        $iterationsLimit = $input->getOption('iterations');
        $iterationsLimit = $iterationsLimit === null ? $iterationsLimit : (int)$iterationsLimit;
        if ($iterationsLimit !== null && $iterationsLimit <= 0) {
            throw new InvalidArgumentException('Iterations limit option value should be greater than 0');
        }

        return new ExecutionContext(
            $input,
            $output,
            $pause,
            $memoryLimit,
            $timeLimit,
            $iterationsLimit
        );
    }

    private function registerSignals(ExecutionContext $context): void
    {
        if (extension_loaded('pcntl')) {
            if (!function_exists('pcntl_signal')) {
                throw new BadFunctionCallException("Function 'pcntl_signal' is referenced in the php.ini 'disable_functions' and can't be called.");
            }
            pcntl_signal(SIGTERM, [$context, 'stopAsap']);
            pcntl_signal(SIGINT, [$context, 'stopAsap']);
        }
    }

    private function checkStop(ExecutionContext $context): bool
    {
        if ($context->isStopAsap()) {
            return true;
        }
        if ($context->getLimitIterations() !== null && $context->getIterationsCount() >= $context->getLimitIterations()) {
            return true;
        }
        if ($context->getLimitTime() !== null && $context->getExecutionTime() >= $context->getLimitTime()) {
            return true;
        }
        if ($context->getMemoryLimit() !== -1 && memory_get_usage(true) >= $context->getMemoryLimit()) {
            return true;
        }
        return false;
    }

    protected function pause(int $seconds, ExecutionContext $context): void
    {
        if ($seconds <= 0) {
            throw new InvalidArgumentException('Pause time argument value should be greater than 0');
        }
        sleep($seconds);
        $context->increaseTotalPauseTime($seconds);
    }

    protected function stop(ExecutionContext $context): void
    {
        $context->stopAsap();
    }
}
