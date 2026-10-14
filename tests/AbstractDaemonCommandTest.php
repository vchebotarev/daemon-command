<?php

declare(strict_types=1);

namespace Chebur\DaemonCommand\Tests;

use Chebur\DaemonCommand\Tests\Fixture\TestDaemonCommand;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class AbstractDaemonCommandTest extends TestCase
{
    public function testPauseOptionRejectsNegativeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Pause time option value should be greater than or equal to 0');

        $this->executeCommand(['--pause' => '-1', '--iterations' => '1']);
    }

    public function testMemoryOptionRejectsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Memory limit option value should be greater than 0 or equal to -1');

        $this->executeCommand(['--memory' => '0', '--iterations' => '1']);
    }

    public function testMemoryOptionRejectsLessThanMinusOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Memory limit option value should be greater than 0 or equal to -1');

        $this->executeCommand(['--memory' => '-2', '--iterations' => '1']);
    }

    public function testTimeOptionRejectsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Time limit option value should be greater than 0');

        $this->executeCommand(['--time' => '0', '--iterations' => '1']);
    }

    public function testIterationsOptionRejectsZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Iterations limit option value should be greater than 0');

        $this->executeCommand(['--iterations' => '0']);
    }

    public function testIterationsLimitRunsExactCount(): void
    {
        $command = new TestDaemonCommand();
        $tester = new CommandTester($command);

        $status = $tester->execute(['--iterations' => '3']);

        self::assertSame(0, $status);
        self::assertSame(3, $command->getExecuteIterationCount());
        self::assertSame(3, $command->getLastContext()->getIterationsCount());
    }

    public function testHookOrder(): void
    {
        $command = new TestDaemonCommand();
        $tester = new CommandTester($command);

        $tester->execute(['--iterations' => '3']);

        self::assertSame(1, $command->getBeforeCycleCount());
        self::assertSame(3, $command->getExecuteIterationCount());
        self::assertSame(3, $command->getAfterIterationCount());
        self::assertSame(1, $command->getAfterCycleCount());
    }

    public function testAfterIterationReceivesIsLastOnFinalIteration(): void
    {
        $command = new TestDaemonCommand();
        $tester = new CommandTester($command);

        $tester->execute(['--iterations' => '3']);

        self::assertSame([false, false, true], $command->getAfterIterationIsLastFlags());
    }

    public function testPauseAccumulatesTotalPauseTime(): void
    {
        $command = new TestDaemonCommand();
        $tester = new CommandTester($command);

        $status = $tester->execute(['--pause' => '1', '--iterations' => '2']);

        self::assertSame(0, $status);
        self::assertSame(1, $command->getLastContext()->getTotalPauseTime());
    }

    public function testStopFromIterationHaltsLoop(): void
    {
        $command = (new TestDaemonCommand())->stopOnIteration(2);
        $tester = new CommandTester($command);

        $status = $tester->execute(['--iterations' => '10']);

        self::assertSame(0, $status);
        self::assertSame(2, $command->getExecuteIterationCount());
        self::assertSame([false, true], $command->getAfterIterationIsLastFlags());
        self::assertTrue($command->getLastContext()->isStopAsap());
    }

    public function testTimeLimitStopsCycle(): void
    {
        $command = (new TestDaemonCommand())->setIterationSleepSeconds(1);
        $tester = new CommandTester($command);

        $status = $tester->execute(['--time' => '1']);

        self::assertSame(0, $status);
        self::assertGreaterThanOrEqual(1, $command->getExecuteIterationCount());
        self::assertLessThan(10, $command->getExecuteIterationCount());
        self::assertGreaterThanOrEqual(1.0, $command->getLastContext()->getExecutionTime());
    }

    public function testScheduleSmokeWithIterationsLimit(): void
    {
        $command = new TestDaemonCommand();
        $tester = new CommandTester($command);

        $status = $tester->execute([
            '--schedule' => '* * * * *',
            '--iterations' => '1',
        ]);

        self::assertSame(0, $status);
        self::assertSame(1, $command->getExecuteIterationCount());
    }

    public function testOutputContainsSummary(): void
    {
        $command = new TestDaemonCommand();
        $tester = new CommandTester($command);

        $tester->execute(['--iterations' => '2']);
        $display = $tester->getDisplay();

        self::assertStringContainsString('Total execution time:', $display);
        self::assertStringContainsString('Iterations count:', $display);
        self::assertStringContainsString('2', $display);
    }

    /**
     * @param array<string, string> $input
     */
    private function executeCommand(array $input): int
    {
        $tester = new CommandTester(new TestDaemonCommand());

        return $tester->execute($input);
    }
}
