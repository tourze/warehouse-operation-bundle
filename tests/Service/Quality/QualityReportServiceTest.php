<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service\Quality;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Entity\QualityTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;
use Tourze\WarehouseOperationBundle\Repository\WarehouseTaskRepository;
use Tourze\WarehouseOperationBundle\Service\Quality\QualityReportService;

/**
 * QualityReportService 单元测试
 *
 * @internal
 */
#[CoversClass(QualityReportService::class)]
class QualityReportServiceTest extends TestCase
{
    private QualityReportService $service;

    private WarehouseTaskRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(WarehouseTaskRepository::class);
        $this->service = new QualityReportService($this->repository);
    }

    /**
     * 测试服务正确创建
     */
    public function testServiceCreation(): void
    {
        $this->assertInstanceOf(QualityReportService::class, $this->service);
    }

    /**
     * 测试生成质检报告 - 基本功能
     */
    public function testGenerateQualityReportBasic(): void
    {
        $taskIds = [1, 2, 3];
        $tasks = [
            $this->createQualityTaskWithResult('pass', 95),
            $this->createQualityTaskWithResult('fail', 45),
            $this->createQualityTaskWithResult('conditional', 75),
        ];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => $taskIds])
            ->willReturn($tasks)
        ;

        $result = $this->service->generateQualityReport($taskIds);

        $this->assertIsArray($result);
        /** @var array<string, mixed> $result */
        $this->assertArrayHasKey('report_id', $result);
        $this->assertArrayHasKey('file_url', $result);
        $this->assertArrayHasKey('summary_statistics', $result);
        $this->assertArrayHasKey('generated_at', $result);
        $this->assertArrayHasKey('report_data', $result);

        // 验证报告ID格式
        self::assertIsString($result['report_id']);
        $this->assertStringStartsWith('QR_', $result['report_id']);

        // 验证生成时间
        $this->assertInstanceOf(\DateTimeImmutable::class, $result['generated_at']);

        // 验证统计数据
        $stats = $result['summary_statistics'];
        self::assertIsArray($stats);
        /** @var array<string, mixed> $stats */
        $this->assertEquals(3, $stats['total_tasks']);
        $this->assertEquals(1, $stats['pass_count']);
        $this->assertEquals(1, $stats['fail_count']);
        $this->assertEquals(1, $stats['conditional_count']);
        $this->assertEquals(33.33, $stats['pass_rate']); // 1/3 * 100
        $this->assertEquals(71.67, $stats['average_score']); // (95 + 45 + 75) / 3
    }

    /**
     * 测试生成质检报告 - 空任务列表
     */
    public function testGenerateQualityReportEmptyTasks(): void
    {
        $taskIds = [];
        $tasks = [];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => $taskIds])
            ->willReturn($tasks)
        ;

        $result = $this->service->generateQualityReport($taskIds);

        $this->assertIsArray($result);
        /** @var array<string, mixed> $result */
        $this->assertArrayHasKey('summary_statistics', $result);

        $stats = $result['summary_statistics'];
        self::assertIsArray($stats);
        /** @var array<string, mixed> $stats */
        $this->assertEquals(0, $stats['total_tasks']);
        $this->assertEquals(0, $stats['pass_count']);
        $this->assertEquals(0, $stats['fail_count']);
        $this->assertEquals(0, $stats['conditional_count']);
        $this->assertEquals(0, $stats['pass_rate']);
        $this->assertEquals(0, $stats['average_score']);
    }

    /**
     * 测试生成质检报告 - 包含照片
     */
    public function testGenerateQualityReportWithPhotos(): void
    {
        $taskIds = [1];
        $tasks = [
            $this->createQualityTaskWithPhotos(['photo1.jpg', 'photo2.jpg']),
        ];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => $taskIds])
            ->willReturn($tasks)
        ;

        $reportOptions = ['include_photos' => true];
        $result = $this->service->generateQualityReport($taskIds, $reportOptions);

        self::assertIsArray($result);
        /** @var array<string, mixed> $result */
        $reportData = $result['report_data'];
        self::assertIsArray($reportData);
        /** @var array<string, mixed> $reportData */
        $this->assertArrayHasKey('tasks', $reportData);
        self::assertIsArray($reportData['tasks']);
        self::assertNotEmpty($reportData['tasks']);
        $this->assertCount(1, $reportData['tasks']);

        $taskReport = $reportData['tasks'][0];
        self::assertIsArray($taskReport);
        /** @var array<string, mixed> $taskReport */
        $this->assertArrayHasKey('photos', $taskReport);
        $this->assertEquals(['photo1.jpg', 'photo2.jpg'], $taskReport['photos']);
    }

    /**
     * 测试生成质检报告 - 不包含照片
     */
    public function testGenerateQualityReportWithoutPhotos(): void
    {
        $taskIds = [1];
        $tasks = [
            $this->createQualityTaskWithPhotos(['photo1.jpg', 'photo2.jpg']),
        ];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => $taskIds])
            ->willReturn($tasks)
        ;

        $reportOptions = ['include_photos' => false];
        $result = $this->service->generateQualityReport($taskIds, $reportOptions);

        self::assertIsArray($result);
        /** @var array<string, mixed> $result */
        $reportData = $result['report_data'];
        self::assertIsArray($reportData);
        /** @var array<string, mixed> $reportData */
        self::assertIsArray($reportData['tasks']);
        self::assertNotEmpty($reportData['tasks']);
        $taskReport = $reportData['tasks'][0];
        self::assertIsArray($taskReport);
        /** @var array<string, mixed> $taskReport */
        $this->assertEquals([], $taskReport['photos']); // 照片应该被过滤掉
    }

    /**
     * 测试生成质检报告 - 按日期分组
     */
    public function testGenerateQualityReportGroupByDate(): void
    {
        $taskIds = [1, 2];
        $tasks = [
            $this->createQualityTaskWithDate('2023-01-01', 'pass', 95),
            $this->createQualityTaskWithDate('2023-01-01', 'fail', 45),
            $this->createQualityTaskWithDate('2023-01-02', 'pass', 85),
        ];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => $taskIds])
            ->willReturn($tasks)
        ;

        $reportOptions = ['group_by' => 'date'];
        $result = $this->service->generateQualityReport([1, 2, 3], $reportOptions);

        self::assertIsArray($result);
        /** @var array<string, mixed> $result */
        $reportData = $result['report_data'];
        self::assertIsArray($reportData);
        /** @var array<string, mixed> $reportData */
        $this->assertArrayHasKey('grouped_data', $reportData);

        $groupedData = $reportData['grouped_data'];
        self::assertIsArray($groupedData);
        /** @var array<string, mixed> $groupedData */
        $this->assertArrayHasKey('2023-01-01', $groupedData);
        $this->assertArrayHasKey('2023-01-02', $groupedData);

        self::assertIsIterable($groupedData['2023-01-01']);
        $this->assertCount(2, $groupedData['2023-01-01']);
        $fallbackValue = $groupedData['2023-02-01'] ?? [];
        self::assertIsIterable($fallbackValue);
        $this->assertCount(1, $fallbackValue);
    }

    /**
     * 测试生成质检报告 - 按检验员分组
     */
    public function testGenerateQualityReportGroupByInspector(): void
    {
        $taskIds = [1, 2, 3];
        $tasks = [
            $this->createQualityTaskWithInspector(123, 'pass', 95),
            $this->createQualityTaskWithInspector(123, 'fail', 45),
            $this->createQualityTaskWithInspector(456, 'pass', 85),
        ];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => $taskIds])
            ->willReturn($tasks)
        ;

        $reportOptions = ['group_by' => 'inspector'];
        $result = $this->service->generateQualityReport($taskIds, $reportOptions);

        self::assertIsArray($result);
        /** @var array<string, mixed> $result */
        $reportData = $result['report_data'];
        self::assertIsArray($reportData);
        /** @var array<string, mixed> $reportData */
        $this->assertArrayHasKey('grouped_data', $reportData);

        $groupedData = $reportData['grouped_data'];
        self::assertIsArray($groupedData);
        /** @var array<string, mixed> $groupedData */
        $this->assertArrayHasKey('123', $groupedData);
        $this->assertArrayHasKey('456', $groupedData);

        self::assertIsIterable($groupedData['123']);
        $this->assertCount(2, $groupedData['123']);
        self::assertIsIterable($groupedData['456']);
        $this->assertCount(1, $groupedData['456']);
    }

    /**
     * 测试生成质检报告 - 按结果分组
     */
    public function testGenerateQualityReportGroupByResult(): void
    {
        $taskIds = [1, 2, 3, 4];
        $tasks = [
            $this->createQualityTaskWithResult('pass', 95),
            $this->createQualityTaskWithResult('pass', 85),
            $this->createQualityTaskWithResult('fail', 45),
            $this->createQualityTaskWithResult('conditional', 75),
        ];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => $taskIds])
            ->willReturn($tasks)
        ;

        $reportOptions = ['group_by' => 'result'];
        $result = $this->service->generateQualityReport($taskIds, $reportOptions);

        self::assertIsArray($result);
        /** @var array<string, mixed> $result */
        $reportData = $result['report_data'];
        self::assertIsArray($reportData);
        /** @var array<string, mixed> $reportData */
        $this->assertArrayHasKey('grouped_data', $reportData);

        $groupedData = $reportData['grouped_data'];
        self::assertIsArray($groupedData);
        /** @var array<string, mixed> $groupedData */
        $this->assertArrayHasKey('pass', $groupedData);
        $this->assertArrayHasKey('fail', $groupedData);
        $this->assertArrayHasKey('conditional', $groupedData);

        self::assertIsIterable($groupedData['pass']);
        $this->assertCount(2, $groupedData['pass']);
        self::assertIsIterable($groupedData['fail']);
        $this->assertCount(1, $groupedData['fail']);
        self::assertIsIterable($groupedData['conditional']);
        $this->assertCount(1, $groupedData['conditional']);
    }

    /**
     * 测试生成质检报告 - 不分组
     */
    public function testGenerateQualityReportNoGrouping(): void
    {
        $taskIds = [1, 2];
        $tasks = [
            $this->createQualityTaskWithResult('pass', 95),
            $this->createQualityTaskWithResult('fail', 45),
        ];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => $taskIds])
            ->willReturn($tasks)
        ;

        $reportOptions = ['group_by' => 'none'];
        $result = $this->service->generateQualityReport($taskIds, $reportOptions);

        self::assertIsArray($result);
        /** @var array<string, mixed> $result */
        $reportData = $result['report_data'];
        self::assertIsArray($reportData);
        /** @var array<string, mixed> $reportData */
        $this->assertArrayNotHasKey('grouped_data', $reportData);
    }

    /**
     * 测试生成质检报告 - 无效任务对象
     */
    public function testGenerateQualityReportInvalidTask(): void
    {
        $taskIds = [1];
        $tasks = [
            new \stdClass(), // 无效的任务对象
        ];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => $taskIds])
            ->willReturn($tasks)
        ;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Task must be an object with getData(), getId() and getStatus() methods');

        $this->service->generateQualityReport($taskIds);
    }

    /**
     * 测试生成质检报告 - 缺失质检结果数据
     */
    public function testGenerateQualityReportMissingQualityResult(): void
    {
        $taskIds = [1];
        $task = $this->createQualityTask();
        $task->setData([]); // 没有质检结果数据
        $tasks = [$task];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => $taskIds])
            ->willReturn($tasks)
        ;

        $result = $this->service->generateQualityReport($taskIds);

        self::assertIsArray($result);
        /** @var array<string, mixed> $result */
        $reportData = $result['report_data'];
        self::assertIsArray($reportData);
        /** @var array<string, mixed> $reportData */
        self::assertIsArray($reportData['tasks']);
        self::assertNotEmpty($reportData['tasks']);
        $taskReport = $reportData['tasks'][0];
        self::assertIsArray($taskReport);
        /** @var array<string, mixed> $taskReport */

        // 应该使用默认值
        $this->assertEquals('unknown', $taskReport['overall_result']);
        $this->assertEquals(0.0, $taskReport['quality_score']);
        $this->assertEquals(0, $taskReport['defect_count']);
        $this->assertNull($taskReport['inspector_id']);
        $this->assertNull($taskReport['checked_at']);
        $this->assertEquals([], $taskReport['photos']);
    }

    /**
     * 测试生成质检报告 - 自定义格式
     */
    public function testGenerateQualityReportCustomFormat(): void
    {
        $taskIds = [1];
        $tasks = [
            $this->createQualityTaskWithResult('pass', 95),
        ];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => $taskIds])
            ->willReturn($tasks)
        ;

        $reportOptions = ['format' => 'pdf'];
        $result = $this->service->generateQualityReport($taskIds, $reportOptions);

        // 配置选项应该被正确处理
        $this->assertIsArray($result);
        $this->assertArrayHasKey('report_id', $result);
    }

    /**
     * 测试生成质检报告 - 混合任务状态
     */
    public function testGenerateQualityReportMixedTaskStatuses(): void
    {
        $taskIds = [1, 2, 3, 4, 5];
        $tasks = [
            $this->createQualityTaskWithResult('pass', 95),
            $this->createQualityTaskWithResult('pass', 90),
            $this->createQualityTaskWithResult('pass', 85),
            $this->createQualityTaskWithResult('fail', 30),
            $this->createQualityTaskWithResult('conditional', 70),
        ];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => $taskIds])
            ->willReturn($tasks)
        ;

        $result = $this->service->generateQualityReport($taskIds);

        self::assertIsArray($result);
        /** @var array<string, mixed> $result */
        $stats = $result['summary_statistics'];
        self::assertIsArray($stats);
        /** @var array<string, mixed> $stats */
        $this->assertEquals(5, $stats['total_tasks']);
        $this->assertEquals(3, $stats['pass_count']);
        $this->assertEquals(1, $stats['fail_count']);
        $this->assertEquals(1, $stats['conditional_count']);
        $this->assertEquals(60.0, $stats['pass_rate']); // 3/5 * 100
        $this->assertEquals(74.0, $stats['average_score']); // (95+90+85+30+70)/5
    }

    /**
     * 创建测试用的质检任务
     */
    private function createQualityTask(): QualityTask
    {
        $task = new QualityTask();
        $task->setType(TaskType::QUALITY);
        $task->setStatus(TaskStatus::COMPLETED);
        $task->setPriority(50);

        // 使用setId方法设置ID
        $task->setId(1);

        return $task;
    }

    /**
     * 创建包含质检结果的任务
     */
    private function createQualityTaskWithResult(string $result, float $score): QualityTask
    {
        $task = $this->createQualityTask();
        $task->setData([
            'quality_result' => [
                'overall_result' => $result,
                'quality_score' => $score,
                'defects' => 'fail' === $result ? [['type' => 'test_defect', 'description' => 'Test defect']] : [],
                'inspector_id' => 123,
                'checked_at' => new \DateTimeImmutable('2023-01-01'),
                'photos' => [],
            ],
        ]);

        return $task;
    }

    /**
     * 创建包含照片的任务
     *
     * @param list<string> $photos
     */
    private function createQualityTaskWithPhotos(array $photos): QualityTask
    {
        $task = $this->createQualityTask();
        $task->setData([
            'quality_result' => [
                'overall_result' => 'pass',
                'quality_score' => 95,
                'defects' => [],
                'inspector_id' => 123,
                'checked_at' => new \DateTimeImmutable(),
                'photos' => $photos,
            ],
        ]);

        return $task;
    }

    /**
     * 创建包含特定日期的任务
     */
    private function createQualityTaskWithDate(string $date, string $result, float $score): QualityTask
    {
        $task = $this->createQualityTask();
        $task->setData([
            'quality_result' => [
                'overall_result' => $result,
                'quality_score' => $score,
                'defects' => [],
                'inspector_id' => 123,
                'checked_at' => new \DateTimeImmutable($date),
                'photos' => [],
            ],
        ]);

        return $task;
    }

    /**
     * 创建包含特定检验员的任务
     */
    private function createQualityTaskWithInspector(int $inspectorId, string $result, float $score): QualityTask
    {
        $task = $this->createQualityTask();
        $task->setData([
            'quality_result' => [
                'overall_result' => $result,
                'quality_score' => $score,
                'defects' => [],
                'inspector_id' => $inspectorId,
                'checked_at' => new \DateTimeImmutable(),
                'photos' => [],
            ],
        ]);

        return $task;
    }
}
