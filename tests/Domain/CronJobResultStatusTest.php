<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Tests\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shapecode\Bundle\CronBundle\Domain\CronJobResultStatus;

#[CoversClass(CronJobResultStatus::class)]
class CronJobResultStatusTest extends TestCase
{
    #[DataProvider('fromCommandStatusProvider')]
    public function testFromCommandStatus(int $statusCode, CronJobResultStatus $expected): void
    {
        self::assertSame($expected, CronJobResultStatus::fromCommandStatus($statusCode));
    }

    /** @return array<string, array{int, CronJobResultStatus}> */
    public static function fromCommandStatusProvider(): array
    {
        return [
            'success' => [0, CronJobResultStatus::SUCCEEDED],
            'skipped' => [2, CronJobResultStatus::SKIPPED],
            'failed with code 1' => [1, CronJobResultStatus::FAILED],
            'failed with code 3' => [3, CronJobResultStatus::FAILED],
            'failed with code 127' => [127, CronJobResultStatus::FAILED],
            'failed with negative code' => [-1, CronJobResultStatus::FAILED],
        ];
    }

    #[DataProvider('getStatusMessageProvider')]
    public function testGetStatusMessage(CronJobResultStatus $status, string $expected): void
    {
        self::assertSame($expected, $status->getStatusMessage());
    }

    /** @return array<string, array{CronJobResultStatus, string}> */
    public static function getStatusMessageProvider(): array
    {
        return [
            'succeeded' => [CronJobResultStatus::SUCCEEDED, 'succeeded'],
            'failed' => [CronJobResultStatus::FAILED, 'failed'],
            'skipped' => [CronJobResultStatus::SKIPPED, 'skipped'],
        ];
    }

    #[DataProvider('getBlockNameProvider')]
    public function testGetBlockName(CronJobResultStatus $status, string $expected): void
    {
        self::assertSame($expected, $status->getBlockName());
    }

    /** @return array<string, array{CronJobResultStatus, string}> */
    public static function getBlockNameProvider(): array
    {
        return [
            'succeeded' => [CronJobResultStatus::SUCCEEDED, 'success'],
            'failed' => [CronJobResultStatus::FAILED, 'error'],
            'skipped' => [CronJobResultStatus::SKIPPED, 'info'],
        ];
    }

    public function testAllEnumCasesExist(): void
    {
        $cases = CronJobResultStatus::cases();

        // Verify all expected cases exist
        self::assertContains(CronJobResultStatus::SUCCEEDED, $cases);
        self::assertContains(CronJobResultStatus::FAILED, $cases);
        self::assertContains(CronJobResultStatus::SKIPPED, $cases);
    }
}
