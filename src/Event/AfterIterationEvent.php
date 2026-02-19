<?php

declare(strict_types=1);

namespace Chebur\DaemonCommand\Event;

use Chebur\DaemonCommand\AbstractDaemonCommand;
use Chebur\DaemonCommand\ExecutionContext;
use Symfony\Contracts\EventDispatcher\Event;

class AfterIterationEvent extends Event
{
    public function __construct(
        public readonly AbstractDaemonCommand $command,
        public readonly ExecutionContext $executionContext,
        public readonly bool $isLastIteration = false,
    ) {
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
