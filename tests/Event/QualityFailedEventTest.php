<?php

namespace Tourze\WarehouseOperationBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Entity\QualityTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;
use Tourze\WarehouseOperationBundle\Event\QualityFailedEvent;

/**
 * QualityFailedEvent 单元测试
 *
 * @internal
 */
#[CoversClass(QualityFailedEvent::class)]
class QualityFailedEventTest extends TestCase
{
    private QualityTask $qualityTask;

    public function testConstructor(): void
    {
        $failureReason = 'Appearance defects found';
        $failureDetails = [
            'type' => 'appearance',
            'severity' => 'high',
            'requires_isolation' => true,
            'defects' => ['scratches', 'dents'],
        ];
        $context = ['operator_id' => 101, 'timestamp' => '2025-09-04T22:00:00Z'];

        $event = new QualityFailedEvent(
            $this->qualityTask,
            $failureReason,
            $failureDetails,
            $context
        );

        $this->assertSame($this->qualityTask, $event->getQualityTask());
        $this->assertSame($failureReason, $event->getFailureReason());
        $this->assertSame($failureDetails, $event->getFailureDetails());
        $this->assertSame($context, $event->getContext());
    }

    public function testConstructorWithMinimalParameters(): void
    {
        $failureReason = 'Basic quality check failed';

        $event = new QualityFailedEvent($this->qualityTask, $failureReason);

        $this->assertSame($this->qualityTask, $event->getQualityTask());
        $this->assertSame($failureReason, $event->getFailureReason());
        $this->assertSame([], $event->getFailureDetails());
        $this->assertSame([], $event->getContext());
    }

    public function testGetTask(): void
    {
        $event = new QualityFailedEvent($this->qualityTask, 'Test failure');

        $this->assertSame($this->qualityTask, $event->getTask());
        $this->assertSame($this->qualityTask, $event->getQualityTask());
    }

    public function testHasFailureType(): void
    {
        $failureDetails = [
            'type' => 'weight',
            'expected_weight' => 500,
            'actual_weight' => 480,
        ];

        $event = new QualityFailedEvent(
            $this->qualityTask,
            'Weight mismatch',
            $failureDetails
        );

        $this->assertTrue($event->hasFailureType('weight'));
        $this->assertFalse($event->hasFailureType('appearance'));
        $this->assertFalse($event->hasFailureType('dimension'));
    }

    public function testHasFailureTypeWithoutType(): void
    {
        $event = new QualityFailedEvent($this->qualityTask, 'Generic failure');

        $this->assertFalse($event->hasFailureType('weight'));
        $this->assertFalse($event->hasFailureType('appearance'));
    }

    public function testGetFailureSeverity(): void
    {
        $failureDetailsWithSeverity = ['severity' => 'critical'];
        $event1 = new QualityFailedEvent(
            $this->qualityTask,
            'Critical failure',
            $failureDetailsWithSeverity
        );

        $this->assertSame('critical', $event1->getFailureSeverity());

        $event2 = new QualityFailedEvent($this->qualityTask, 'Normal failure');
        $this->assertSame('medium', $event2->getFailureSeverity());
    }

    public function testGetFailureSeverityVariousLevels(): void
    {
        $severityLevels = ['low', 'medium', 'high', 'critical'];

        foreach ($severityLevels as $severity) {
            $event = new QualityFailedEvent(
                $this->qualityTask,
                'Test failure',
                ['severity' => $severity]
            );

            $this->assertSame($severity, $event->getFailureSeverity());
        }
    }

    public function testRequiresProductIsolation(): void
    {
        $failureDetailsWithIsolation = ['requires_isolation' => true];
        $event1 = new QualityFailedEvent(
            $this->qualityTask,
            'Severe defect',
            $failureDetailsWithIsolation
        );

        $this->assertTrue($event1->requiresProductIsolation());

        $failureDetailsWithoutIsolation = ['requires_isolation' => false];
        $event2 = new QualityFailedEvent(
            $this->qualityTask,
            'Minor issue',
            $failureDetailsWithoutIsolation
        );

        $this->assertFalse($event2->requiresProductIsolation());

        $event3 = new QualityFailedEvent($this->qualityTask, 'Normal failure');
        $this->assertFalse($event3->requiresProductIsolation());
    }

    public function testComplexFailureScenario(): void
    {
        $failureDetails = [
            'type' => 'multiple',
            'severity' => 'high',
            'requires_isolation' => true,
            'issues' => [
                'appearance' => ['scratches', 'discoloration'],
                'dimension' => ['width_variance' => 2.5],
                'weight' => ['underweight' => 15],
            ],
            'inspection_time' => '2025-09-04T22:00:00Z',
            'inspector_notes' => 'Multiple quality issues detected',
        ];

        $context = [
            'operator_id' => 205,
            'station' => 'QC-Station-3',
            'batch_id' => 'B20250904001',
            'product_sku' => 'PROD-12345',
        ];

        $event = new QualityFailedEvent(
            $this->qualityTask,
            'Multiple quality defects detected',
            $failureDetails,
            $context
        );

        $this->assertSame('Multiple quality defects detected', $event->getFailureReason());
        $this->assertTrue($event->hasFailureType('multiple'));
        $this->assertSame('high', $event->getFailureSeverity());
        $this->assertTrue($event->requiresProductIsolation());

        $eventContext = $event->getContext();
        $this->assertIsArray($eventContext);
        $this->assertArrayHasKey('operator_id', $eventContext);
        $this->assertSame(205, $eventContext['operator_id']);

        $failureDetails = $event->getFailureDetails();
        $this->assertIsArray($failureDetails);
        $this->assertArrayHasKey('issues', $failureDetails);
        $this->assertIsArray($failureDetails['issues']);
        $this->assertArrayHasKey('appearance', $failureDetails['issues']);
        $this->assertSame(['scratches', 'discoloration'], $failureDetails['issues']['appearance']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->qualityTask = new QualityTask();
        $this->qualityTask->setType(TaskType::QUALITY);
        $this->qualityTask->setStatus(TaskStatus::FAILED);
        $this->qualityTask->setPriority(80);
    }
}
