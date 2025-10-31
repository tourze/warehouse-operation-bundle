<?php

namespace Tourze\WarehouseOperationBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\WarehouseOperationBundle\Entity\QualityTask;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;

/**
 * @internal
 */
#[CoversClass(QualityTask::class)]
final class QualityTaskTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new QualityTask();
    }

    /**
     * @return iterable<string, array{string, \DateTimeImmutable}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'createTime' => ['createTime', new \DateTimeImmutable()],
            'updateTime' => ['updateTime', new \DateTimeImmutable()],
        ];
    }

    private QualityTask $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->task = new QualityTask();
    }

    public function testGetIdInitiallyNull(): void
    {
        $this->assertNull($this->task->getId());
    }

    public function testTypeGetterAndSetter(): void
    {
        $this->task->setType(TaskType::QUALITY);
        $this->assertSame(TaskType::QUALITY, $this->task->getType());

        $this->task->setType(TaskType::COUNT);
        $this->assertSame(TaskType::COUNT, $this->task->getType());
    }

    public function testStatusDefaultsToPending(): void
    {
        $this->assertSame(TaskStatus::PENDING, $this->task->getStatus());
    }

    public function testStatusGetterAndSetter(): void
    {
        $this->task->setStatus(TaskStatus::ASSIGNED);
        $this->assertSame(TaskStatus::ASSIGNED, $this->task->getStatus());

        $this->task->setStatus(TaskStatus::IN_PROGRESS);
        $this->assertSame(TaskStatus::IN_PROGRESS, $this->task->getStatus());

        $this->task->setStatus(TaskStatus::PAUSED);
        $this->assertSame(TaskStatus::PAUSED, $this->task->getStatus());

        $this->task->setStatus(TaskStatus::COMPLETED);
        $this->assertSame(TaskStatus::COMPLETED, $this->task->getStatus());

        $this->task->setStatus(TaskStatus::FAILED);
        $this->assertSame(TaskStatus::FAILED, $this->task->getStatus());
    }

    public function testPriorityDefaultsToOne(): void
    {
        $this->assertSame(1, $this->task->getPriority());
    }

    public function testPriorityGetterAndSetter(): void
    {
        // 质检任务通常优先级较高
        $this->task->setPriority(9);
        $this->assertSame(9, $this->task->getPriority());

        $this->task->setPriority(4);
        $this->assertSame(4, $this->task->getPriority());
    }

    public function testDataDefaultsToEmptyArray(): void
    {
        $this->assertEquals([], $this->task->getData());
    }

    public function testDataGetterAndSetter(): void
    {
        $data = [
            'batch_id' => 'BATCH001',
            'supplier_id' => 'SUP002',
            'inspection_type' => 'incoming',
            'items' => [
                [
                    'sku' => 'PROD005',
                    'sample_size' => 10,
                    'defect_count' => 0,
                    'inspection_result' => 'passed',
                ],
            ],
            'quality_standards' => [
                'appearance' => 'good',
                'functionality' => 'tested',
                'packaging' => 'intact',
            ],
        ];
        $this->task->setData($data);
        $this->assertEquals($data, $this->task->getData());

        // 测试更新检验结果
        $data['items'][0]['defect_count'] = 1;
        $data['items'][0]['inspection_result'] = 'failed';
        $this->task->setData($data);
        $this->assertEquals($data, $this->task->getData());
    }

    public function testAssignedWorkerInitiallyNull(): void
    {
        $this->assertNull($this->task->getAssignedWorker());
    }

    public function testAssignedWorkerGetterAndSetter(): void
    {
        // 质检员 ID
        $this->task->setAssignedWorker(101);
        $this->assertSame(101, $this->task->getAssignedWorker());

        $this->task->setAssignedWorker(null);
        $this->assertNull($this->task->getAssignedWorker());
    }

    public function testAssignedAtInitiallyNull(): void
    {
        $this->assertNull($this->task->getAssignedAt());
    }

    public function testAssignedAtGetterAndSetter(): void
    {
        $assignedAt = new \DateTimeImmutable('2024-01-18 08:00:00');
        $this->task->setAssignedAt($assignedAt);
        $this->assertSame($assignedAt, $this->task->getAssignedAt());

        $this->task->setAssignedAt(null);
        $this->assertNull($this->task->getAssignedAt());
    }

    public function testStartedAtInitiallyNull(): void
    {
        $this->assertNull($this->task->getStartedAt());
    }

    public function testStartedAtGetterAndSetter(): void
    {
        $startedAt = new \DateTimeImmutable('2024-01-18 08:30:00');
        $this->task->setStartedAt($startedAt);
        $this->assertSame($startedAt, $this->task->getStartedAt());
    }

    public function testCompletedAtInitiallyNull(): void
    {
        $this->assertNull($this->task->getCompletedAt());
    }

    public function testCompletedAtGetterAndSetter(): void
    {
        $completedAt = new \DateTimeImmutable('2024-01-18 10:45:00');
        $this->task->setCompletedAt($completedAt);
        $this->assertSame($completedAt, $this->task->getCompletedAt());
    }

    public function testNotesInitiallyNull(): void
    {
        $this->assertNull($this->task->getNotes());
    }

    public function testNotesGetterAndSetter(): void
    {
        $notes = '质检发现轻微外观问题，但不影响功能，建议接受';
        $this->task->setNotes($notes);
        $this->assertSame($notes, $this->task->getNotes());

        $this->task->setNotes(null);
        $this->assertNull($this->task->getNotes());
    }

    public function testToStringMethod(): void
    {
        // 使用setId方法设置ID
        $this->task->setId(101);

        $this->task->setType(TaskType::QUALITY);

        $this->assertSame('Task #101 (quality)', $this->task->__toString());
    }

    public function testTimestampableAwareTraitMethods(): void
    {
        $createTime = new \DateTimeImmutable('2024-01-18 07:30:00');
        $updateTime = new \DateTimeImmutable('2024-01-18 11:00:00');

        $this->task->setCreateTime($createTime);
        $this->assertSame($createTime, $this->task->getCreateTime());

        $this->task->setUpdateTime($updateTime);
        $this->assertSame($updateTime, $this->task->getUpdateTime());
    }

    public function testBlameableAwareTraitMethods(): void
    {
        $createdBy = 'quality_supervisor';
        $updatedBy = 'inspector_001';

        $this->task->setCreatedBy($createdBy);
        $this->assertSame($createdBy, $this->task->getCreatedBy());

        $this->task->setUpdatedBy($updatedBy);
        $this->assertSame($updatedBy, $this->task->getUpdatedBy());
    }

    public function testCanInstantiateQualityTask(): void
    {
        $qualityTask = new QualityTask();
        $this->assertInstanceOf(QualityTask::class, $qualityTask);
    }

    public function testSetterMethods(): void
    {
        $this->task->setType(TaskType::QUALITY);
        $this->task->setStatus(TaskStatus::IN_PROGRESS);
        $this->task->setPriority(9);
        $this->task->setData(['batch_id' => 'BATCH001']);
        $this->task->setAssignedWorker(101);
        $this->task->setNotes('质检任务进行中');

        $this->assertSame(TaskType::QUALITY, $this->task->getType());
        $this->assertSame(TaskStatus::IN_PROGRESS, $this->task->getStatus());
        $this->assertSame(9, $this->task->getPriority());
        $this->assertSame(['batch_id' => 'BATCH001'], $this->task->getData());
        $this->assertSame(101, $this->task->getAssignedWorker());
        $this->assertSame('质检任务进行中', $this->task->getNotes());
    }

    public function testQualityInspectionWorkflow(): void
    {
        // 测试质检任务状态流转
        $this->assertSame(TaskStatus::PENDING, $this->task->getStatus());

        $this->task->setStatus(TaskStatus::ASSIGNED);
        $this->assertSame(TaskStatus::ASSIGNED, $this->task->getStatus());

        $this->task->setStatus(TaskStatus::IN_PROGRESS);
        $this->assertSame(TaskStatus::IN_PROGRESS, $this->task->getStatus());

        // 质检可能需要暂停
        $this->task->setStatus(TaskStatus::PAUSED);
        $this->assertSame(TaskStatus::PAUSED, $this->task->getStatus());

        // 恢复并完成
        $this->task->setStatus(TaskStatus::IN_PROGRESS);
        $this->task->setStatus(TaskStatus::COMPLETED);
        $this->assertSame(TaskStatus::COMPLETED, $this->task->getStatus());
    }

    public function testQualityInspectionFailure(): void
    {
        // 测试质检失败场景
        $this->task->setStatus(TaskStatus::ASSIGNED);
        $this->task->setStatus(TaskStatus::IN_PROGRESS);
        $this->task->setStatus(TaskStatus::FAILED);

        $this->assertSame(TaskStatus::FAILED, $this->task->getStatus());
    }
}
