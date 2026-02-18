<?php

declare(strict_types=1);

namespace Chebur\DaemonCommand\Event;

use Chebur\DaemonCommand\AbstractDaemonCommand;
use Chebur\DaemonCommand\ExecutionContext;
use Symfony\Contracts\EventDispatcher\Event;

class AfterIterationEvent extends Event
{
    private AbstractDaemonCommand $command;

    private ExecutionContext $executionContext;

    private bool $isLastIteration;

    public function __construct(
        AbstractDaemonCommand $command,
        ExecutionContext $executionContext,
        bool $isLastIteration = false
    ) {
        $this->command = $command;
        $this->executionContext = $executionContext;
        $this->isLastIteration = $isLastIteration;
    }

    public function getCommand(): AbstractDaemonCommand
    {
        return $this->command;
    }

    public function getExecutionContext(): ExecutionContext
    {
        return $this->executionContext;
    }

    public function isLastIteration(): bool
    {
        return $this->isLastIteration;
    }
}
