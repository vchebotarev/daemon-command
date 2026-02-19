<?php

declare(strict_types=1);

namespace Chebur\DaemonCommand;

use Cron\CronExpression;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExecutionContext
{
    private float $timeStart;
    private int $totalPause = 0;
    private int $iterations = 0;
    private bool $stopAsap = false;

    public function __construct(
        private readonly InputInterface $input,
        private readonly OutputInterface $output,
        private readonly int $pause,
        private readonly int $memoryLimit,
        private readonly ?int $timeLimit,
        private readonly ?int $iterationsLimit,
        private readonly ?CronExpression $schedule = null,
    ) {
        $this->timeStart = microtime(true);
    }

    public function getInput(): InputInterface
    {
        return $this->input;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    public function increaseTotalPauseTime(int $seconds): void
    {
        $this->totalPause += $seconds;
    }

    public function getExecutionTime(): float
    {
        return round(microtime(true) - $this->timeStart, 3);
    }

    public function getTotalPauseTime(): int
    {
        return $this->totalPause;
    }

    public function incrementIterations(): void
    {
        $this->iterations++;
    }

    public function getIterationsCount(): int
    {
        return $this->iterations;
    }

    public function getLimitIterations(): ?int
    {
        return $this->iterationsLimit;
    }

    public function getSchedule(): ?CronExpression
    {
        return $this->schedule;
    }

    public function getMemoryLimit(): int
    {
        return $this->memoryLimit;
    }

    public function getLimitTime(): ?int
    {
        return $this->timeLimit;
    }

    public function stopAsap(): void
    {
        $this->stopAsap = true;
    }

    public function isStopAsap(): bool
    {
        return $this->stopAsap;
    }

    public function getPauseBetweenIterations(): int
    {
        return $this->pause;
    }
}
