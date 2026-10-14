<?php

declare(strict_types=1);

namespace Chebur\DaemonCommand\Tests;

use Chebur\DaemonCommand\ExecutionContext;
use Cron\CronExpression;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class ExecutionContextTest extends TestCase
{
    public function testConstructorGetters(): void
    {
        $input = new ArrayInput([]);
        $output = new NullOutput();
        $schedule = new CronExpression('* * * * *');

        $context = new ExecutionContext($input, $output, 5, 128 * 1024 * 1024, 60, 10, $schedule);

        self::assertSame($input, $context->getInput());
        self::assertSame($output, $context->getOutput());
        self::assertSame(5, $context->getPauseBetweenIterations());
        self::assertSame(128 * 1024 * 1024, $context->getMemoryLimit());
        self::assertSame(60, $context->getLimitTime());
        self::assertSame(10, $context->getLimitIterations());
        self::assertSame($schedule, $context->getSchedule());
        self::assertSame(0, $context->getIterationsCount());
        self::assertSame(0, $context->getTotalPauseTime());
        self::assertFalse($context->isStopAsap());
    }

    public function testConstructorDefaultsWithoutSchedule(): void
    {
        $context = new ExecutionContext(new ArrayInput([]), new NullOutput(), 0, -1, null, null);

        self::assertNull($context->getSchedule());
        self::assertNull($context->getLimitTime());
        self::assertNull($context->getLimitIterations());
        self::assertSame(-1, $context->getMemoryLimit());
    }

    public function testIncrementIterations(): void
    {
        $context = $this->createContext();

        self::assertSame(0, $context->getIterationsCount());

        $context->incrementIterations();
        self::assertSame(1, $context->getIterationsCount());

        $context->incrementIterations();
        self::assertSame(2, $context->getIterationsCount());
    }

    public function testIncreaseTotalPauseTime(): void
    {
        $context = $this->createContext();

        self::assertSame(0, $context->getTotalPauseTime());

        $context->increaseTotalPauseTime(3);
        self::assertSame(3, $context->getTotalPauseTime());

        $context->increaseTotalPauseTime(2);
        self::assertSame(5, $context->getTotalPauseTime());
    }

    public function testStopAsap(): void
    {
        $context = $this->createContext();

        self::assertFalse($context->isStopAsap());

        $context->stopAsap();
        self::assertTrue($context->isStopAsap());
    }

    public function testGetExecutionTimeIsNonNegative(): void
    {
        $context = $this->createContext();

        self::assertGreaterThanOrEqual(0.0, $context->getExecutionTime());
    }

    private function createContext(): ExecutionContext
    {
        return new ExecutionContext(new ArrayInput([]), new NullOutput(), 0, -1, null, null);
    }
}
