<?php

namespace Chebur\DaemonCommand;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExecutionContext
{
    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var int
     */
    private $pause = 0;

    /**
     * @var int
     */
    private $totalPause = 0;

    /**
     * @var int
     */
    private $memoryLimit = -1;

    /**
     * @var int|null
     */
    private $timeLimit;

    /**
     * @var int|null
     */
    private $iterationsLimit;

    /**
     * @var float
     */
    private $timeStart;

    /**
     * @var int
     */
    private $iterations = 0;

    /**
     * @var bool
     */
    private $stopAsap = false;

    public function __construct(
        InputInterface $input,
        OutputInterface $output,
        int $pause,
        int $memoryLimit,
        ?int $timeLimit,
        ?int $iterationsLimit
    ) {
        $this->input = $input;
        $this->output = $output;

        $this->pause = $pause;
        $this->memoryLimit = $memoryLimit;
        $this->timeLimit = $timeLimit;
        $this->iterationsLimit = $iterationsLimit;

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
