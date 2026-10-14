<?php

declare(strict_types=1);

namespace Chebur\DaemonCommand;

use BadFunctionCallException;
use Cron\CronExpression;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractDaemonCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('pause', 'p', InputOption::VALUE_REQUIRED, 'Pause between iterations in seconds');
        $this->addOption('memory', 'm', InputOption::VALUE_REQUIRED, 'Memory limit in megabytes');
        $this->addOption('time', 't', InputOption::VALUE_REQUIRED, 'Time limit in seconds');
        $this->addOption('iterations', 'i', InputOption::VALUE_REQUIRED, 'Iterations limit');
        $this->addOption('schedule', 's', InputOption::VALUE_REQUIRED, 'Schedule cron expression');
    }

    abstract protected function executeIteration(ExecutionContext $context): void;

    protected function beforeCycle(ExecutionContext $context): void
    {
    }

    protected function afterCycle(ExecutionContext $context): void
    {
    }

    protected function afterIteration(ExecutionContext $context, bool $isLastIteration): void
    {
        // f.e. doctrine entity manager clear() call
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $context = $this->buildContext($input, $output);
        $this->registerSignals($context);
        $this->beforeCycle($context);
        while (true) {
            if ($context->getSchedule() !== null) {
                $nextRun = $context->getSchedule()->getNextRunDate();
                while ($nextRun->getTimestamp() - time() > 0) {
                    if ($this->checkStop($context)) {
                        break 2;
                    }
                    sleep(1);
                }
            }
            $this->executeIteration($context);
            $context->incrementIterations();
            $checkStop = $this->checkStop($context);
            $this->afterIteration($context, $checkStop);
            if ($checkStop) {
                break;
            }
            if ($context->getPauseBetweenIterations() !== null) {
                $this->pause($context->getPauseBetweenIterations(), $context);
            }
        }
        $this->afterCycle($context);

        $output->writeln('<info>Total execution time:</info> ' . $context->getExecutionTime() . 's');
        if ($context->getTotalPauseTime()) {
            $output->writeln('<info>Total pause time:</info> ' . $context->getTotalPauseTime() . 's');
        }
        $output->writeln('<info>Memory usage:</info> ' . round(memory_get_usage(true) / 1024 / 1024, 3) . 'MB');
        $output->writeln('<info>Iterations count:</info> ' . $context->getIterationsCount());

        return Command::SUCCESS;
    }

    private function buildContext(InputInterface $input, OutputInterface $output): ExecutionContext
    {
        $pause = $this->getIntOption($input, 'pause');
        if ($pause !== null && $pause <= 0) {
            throw new InvalidArgumentException('Pause time option value should be greater than 0');
        }

        $memoryLimit = $this->getIntOption($input, 'memory');
        if ($memoryLimit !== null) {
            if ($memoryLimit <= 0) {
                throw new InvalidArgumentException('Memory limit option value should be greater than 0');
            }
            if (memory_limit_bytes() !== -1 && $memoryLimit * 1024 * 1024 > memory_limit_bytes()) {
                throw new InvalidArgumentException('Memory limit option value should not be greater than "memory_limit" in the php.ini (' . memory_limit() . ')');
            }
        }

        $timeLimit = $this->getIntOption($input, 'time');
        if ($timeLimit !== null && $timeLimit <= 0) {
            throw new InvalidArgumentException('Time limit option value should be greater than 0');
        }

        $iterationsLimit = $this->getIntOption($input, 'iterations');
        if ($iterationsLimit !== null && $iterationsLimit <= 0) {
            throw new InvalidArgumentException('Iterations limit option value should be greater than 0');
        }

        $schedule = $input->getOption('schedule');
        $schedule = $schedule === null ? null : new CronExpression($schedule);

        return new ExecutionContext(
            input: $input,
            output: $output,
            pause: $pause,
            memoryLimit: $memoryLimit,
            timeLimit: $timeLimit,
            iterationsLimit: $iterationsLimit,
            schedule: $schedule,
        );
    }

    private function getIntOption(InputInterface $input, string $name): ?int
    {
        $value = $input->getOption($name);
        if ($value === null) {
            return null;
        }

        $filtered = filter_var($value, FILTER_VALIDATE_INT);
        if ($filtered === false) {
            throw new InvalidArgumentException(sprintf(
                'Option "--%s" expects an integer, got "%s".',
                $name,
                is_scalar($value) ? (string) $value : get_debug_type($value),
            ));
        }

        return $filtered;
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
        if (extension_loaded('pcntl')) {
            if (!function_exists('pcntl_signal_dispatch')) {
                throw new BadFunctionCallException("Function 'pcntl_signal_dispatch' is referenced in the php.ini 'disable_functions' and can't be called.");
            }
            pcntl_signal_dispatch();
        }

        if ($context->isStopAsap()) {
            return true;
        }
        if ($context->getLimitIterations() !== null && $context->getIterationsCount() >= $context->getLimitIterations()) {
            return true;
        }
        if ($context->getLimitTime() !== null && $context->getExecutionTime() >= $context->getLimitTime()) {
            return true;
        }
        if ($context->getMemoryLimit() !== null && memory_get_usage(true) >= $context->getMemoryLimit() * 1024 * 1024) {
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
