<?php

declare(strict_types=1);

namespace Chebur\DaemonCommand\Tests\Fixture;

use Chebur\DaemonCommand\AbstractDaemonCommand;
use Chebur\DaemonCommand\ExecutionContext;

class TestDaemonCommand extends AbstractDaemonCommand
{
    private int $executeIterationCount = 0;

    private int $beforeCycleCount = 0;

    private int $afterCycleCount = 0;

    private int $afterIterationCount = 0;

    /** @var list<bool> */
    private array $afterIterationIsLastFlags = [];

    private ?int $stopOnIteration = null;

    private ?ExecutionContext $lastContext = null;

    private int $iterationSleepSeconds = 0;

    public function __construct()
    {
        parent::__construct('test:daemon');
    }

    public function stopOnIteration(int $iteration): self
    {
        $this->stopOnIteration = $iteration;

        return $this;
    }

    public function setIterationSleepSeconds(int $seconds): self
    {
        $this->iterationSleepSeconds = $seconds;

        return $this;
    }

    public function getExecuteIterationCount(): int
    {
        return $this->executeIterationCount;
    }

    public function getBeforeCycleCount(): int
    {
        return $this->beforeCycleCount;
    }

    public function getAfterCycleCount(): int
    {
        return $this->afterCycleCount;
    }

    public function getAfterIterationCount(): int
    {
        return $this->afterIterationCount;
    }

    /**
     * @return list<bool>
     */
    public function getAfterIterationIsLastFlags(): array
    {
        return $this->afterIterationIsLastFlags;
    }

    public function getLastContext(): ?ExecutionContext
    {
        return $this->lastContext;
    }

    protected function executeIteration(ExecutionContext $context): void
    {
        $this->executeIterationCount++;
        $this->lastContext = $context;

        if ($this->iterationSleepSeconds > 0) {
            sleep($this->iterationSleepSeconds);
        }

        if ($this->stopOnIteration !== null && $this->executeIterationCount === $this->stopOnIteration) {
            $this->stop($context);
        }
    }

    protected function beforeCycle(ExecutionContext $context): void
    {
        $this->beforeCycleCount++;
        $this->lastContext = $context;
    }

    protected function afterCycle(ExecutionContext $context): void
    {
        $this->afterCycleCount++;
        $this->lastContext = $context;
    }

    protected function afterIteration(ExecutionContext $context, bool $isLastIteration): void
    {
        $this->afterIterationCount++;
        $this->afterIterationIsLastFlags[] = $isLastIteration;
        $this->lastContext = $context;
    }
}
