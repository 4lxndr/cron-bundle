<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shapecode\Bundle\CronBundle\CronJob\CommandHelper;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Process\Exception\RuntimeException;

use function realpath;
use function sprintf;

use const PHP_BINARY;

#[CoversClass(CommandHelper::class)]
class CommandHelperTest extends TestCase
{
    public function testGetConsoleBin(): void
    {
        $path = realpath(__DIR__ . '/../Fixtures');
        self::assertIsString($path);

        $kernel = self::createStub(Kernel::class);
        $kernel->method('getProjectDir')->willReturn($path);

        $helper = new CommandHelper($kernel);

        self::assertEquals(
            sprintf('%s/bin/console', $path),
            $helper->getConsoleBin(),
        );
    }

    public function testGetConsoleBinThrowsExceptionWhenNotFound(): void
    {
        $kernel = self::createStub(Kernel::class);
        $kernel->method('getProjectDir')->willReturn('/non/existent/path');

        $helper = new CommandHelper($kernel);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing console binary');
        $this->expectExceptionCode(1653426744265);

        $helper->getConsoleBin();
    }

    public function testGetPhpExecutable(): void
    {
        $kernel = self::createStub(Kernel::class);
        $kernel->method('getProjectDir')->willReturn(__DIR__);

        $helper = new CommandHelper($kernel);

        self::assertEquals(
            PHP_BINARY,
            $helper->getPhpExecutable(),
        );
    }

    public function testGetTimeoutWithNoTimeout(): void
    {
        $kernel = self::createStub(Kernel::class);
        $kernel->method('getProjectDir')->willReturn(__DIR__);

        $helper = new CommandHelper($kernel);

        self::assertNull($helper->getTimeout());
    }

    public function testGetTimeoutWithTimeout(): void
    {
        $kernel = self::createStub(Kernel::class);
        $kernel->method('getProjectDir')->willReturn(__DIR__);

        $helper = new CommandHelper($kernel, 30.0);

        self::assertSame(30.0, $helper->getTimeout());
    }

    public function testGetConsoleBinIsCached(): void
    {
        $path = realpath(__DIR__ . '/../Fixtures');
        self::assertIsString($path);

        $kernel = self::createStub(Kernel::class);
        $kernel->method('getProjectDir')->willReturn($path);

        $helper = new CommandHelper($kernel);

        $firstCall = $helper->getConsoleBin();
        $secondCall = $helper->getConsoleBin();

        self::assertSame($firstCall, $secondCall);
    }

    public function testGetPhpExecutableIsCached(): void
    {
        $kernel = self::createStub(Kernel::class);
        $kernel->method('getProjectDir')->willReturn(__DIR__);

        $helper = new CommandHelper($kernel);

        $firstCall = $helper->getPhpExecutable();
        $secondCall = $helper->getPhpExecutable();

        self::assertSame($firstCall, $secondCall);
    }
}
