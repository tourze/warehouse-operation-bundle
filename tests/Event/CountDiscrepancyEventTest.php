<?php

namespace Tourze\WarehouseOperationBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Entity\CountTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Enum\TaskType;
use Tourze\WarehouseOperationBundle\Event\CountDiscrepancyEvent;

/**
 * CountDiscrepancyEvent 单元测试
 *
 * @internal
 */
#[CoversClass(CountDiscrepancyEvent::class)]
class CountDiscrepancyEventTest extends TestCase
{
    private CountTask $countTask;

    public function testConstructor(): void
    {
        $discrepancyData = [
            'type' => 'quantity',
            'system_quantity' => 100,
            'actual_quantity' => 95,
            'amount' => 250.50,
            'severity' => 'medium',
        ];
        $context = ['operator_id' => 102, 'location' => 'A-01-01'];

        $event = new CountDiscrepancyEvent($this->countTask, $discrepancyData, $context);

        $this->assertSame($this->countTask, $event->getCountTask());
        $this->assertSame($discrepancyData, $event->getDiscrepancyData());
        $this->assertSame($context, $event->getContext());
    }

    public function testConstructorWithMinimalParameters(): void
    {
        $discrepancyData = ['system_quantity' => 50, 'actual_quantity' => 48];

        $event = new CountDiscrepancyEvent($this->countTask, $discrepancyData);

        $this->assertSame($this->countTask, $event->getCountTask());
        $this->assertSame($discrepancyData, $event->getDiscrepancyData());
        $this->assertSame([], $event->getContext());
    }

    public function testGetTask(): void
    {
        $event = new CountDiscrepancyEvent($this->countTask, []);

        $this->assertSame($this->countTask, $event->getTask());
        $this->assertSame($this->countTask, $event->getCountTask());
    }

    public function testGetDiscrepancyType(): void
    {
        $event1 = new CountDiscrepancyEvent($this->countTask, ['type' => 'damage']);
        $this->assertSame('damage', $event1->getDiscrepancyType());

        $event2 = new CountDiscrepancyEvent($this->countTask, []);
        $this->assertSame('quantity', $event2->getDiscrepancyType());
    }

    public function testGetDiscrepancySeverityExplicit(): void
    {
        $event = new CountDiscrepancyEvent(
            $this->countTask,
            ['severity' => 'critical']
        );

        $this->assertSame('critical', $event->getDiscrepancySeverity());
    }

    public function testGetDiscrepancySeverityAutoCalculated(): void
    {
        $testCases = [
            ['amount' => 1500, 'expected' => 'critical'],
            ['amount' => 500, 'expected' => 'high'],
            ['amount' => 50, 'expected' => 'medium'],
            ['amount' => 5, 'expected' => 'low'],
            ['amount' => 0, 'expected' => 'low'],
        ];

        foreach ($testCases as $case) {
            $event = new CountDiscrepancyEvent(
                $this->countTask,
                ['amount' => $case['amount']]
            );

            $this->assertSame($case['expected'], $event->getDiscrepancySeverity());
        }
    }

    public function testGetDiscrepancyAmount(): void
    {
        $event1 = new CountDiscrepancyEvent($this->countTask, ['amount' => 123.45]);
        $this->assertSame(123.45, $event1->getDiscrepancyAmount());

        $event2 = new CountDiscrepancyEvent($this->countTask, []);
        $this->assertSame(0.0, $event2->getDiscrepancyAmount());
    }

    public function testGetSystemQuantity(): void
    {
        $event1 = new CountDiscrepancyEvent($this->countTask, ['system_quantity' => 100]);
        $this->assertSame(100, $event1->getSystemQuantity());

        $event2 = new CountDiscrepancyEvent($this->countTask, []);
        $this->assertSame(0, $event2->getSystemQuantity());
    }

    public function testGetActualQuantity(): void
    {
        $event1 = new CountDiscrepancyEvent($this->countTask, ['actual_quantity' => 95]);
        $this->assertSame(95, $event1->getActualQuantity());

        $event2 = new CountDiscrepancyEvent($this->countTask, []);
        $this->assertSame(0, $event2->getActualQuantity());
    }

    public function testGetQuantityDifference(): void
    {
        $event1 = new CountDiscrepancyEvent(
            $this->countTask,
            ['system_quantity' => 100, 'actual_quantity' => 95]
        );
        $this->assertSame(-5, $event1->getQuantityDifference());

        $event2 = new CountDiscrepancyEvent(
            $this->countTask,
            ['system_quantity' => 80, 'actual_quantity' => 85]
        );
        $this->assertSame(5, $event2->getQuantityDifference());
    }

    public function testIsPositiveDiscrepancy(): void
    {
        $positiveEvent = new CountDiscrepancyEvent(
            $this->countTask,
            ['system_quantity' => 100, 'actual_quantity' => 105]
        );
        $this->assertTrue($positiveEvent->isPositiveDiscrepancy());

        $negativeEvent = new CountDiscrepancyEvent(
            $this->countTask,
            ['system_quantity' => 100, 'actual_quantity' => 95]
        );
        $this->assertFalse($negativeEvent->isPositiveDiscrepancy());
    }

    public function testIsNegativeDiscrepancy(): void
    {
        $negativeEvent = new CountDiscrepancyEvent(
            $this->countTask,
            ['system_quantity' => 100, 'actual_quantity' => 95]
        );
        $this->assertTrue($negativeEvent->isNegativeDiscrepancy());

        $positiveEvent = new CountDiscrepancyEvent(
            $this->countTask,
            ['system_quantity' => 100, 'actual_quantity' => 105]
        );
        $this->assertFalse($positiveEvent->isNegativeDiscrepancy());
    }

    public function testRequiresApprovalBySeverity(): void
    {
        $highSeverityEvent = new CountDiscrepancyEvent(
            $this->countTask,
            ['amount' => 500] // This will be auto-calculated as 'high'
        );
        $this->assertTrue($highSeverityEvent->requiresApproval());

        $criticalSeverityEvent = new CountDiscrepancyEvent(
            $this->countTask,
            ['amount' => 1500] // This will be auto-calculated as 'critical'
        );
        $this->assertTrue($criticalSeverityEvent->requiresApproval());

        $lowSeverityEvent = new CountDiscrepancyEvent(
            $this->countTask,
            ['amount' => 5] // This will be auto-calculated as 'low'
        );
        $this->assertFalse($lowSeverityEvent->requiresApproval());
    }

    public function testRequiresApprovalExplicit(): void
    {
        $event = new CountDiscrepancyEvent(
            $this->countTask,
            ['amount' => 5, 'requires_approval' => true]
        );
        $this->assertTrue($event->requiresApproval());
    }

    public function testGetSuggestedActionsExplicit(): void
    {
        $suggestedActions = ['manual_recount', 'investigate_cause'];
        $event = new CountDiscrepancyEvent(
            $this->countTask,
            ['suggested_actions' => $suggestedActions]
        );

        $this->assertSame($suggestedActions, $event->getSuggestedActions());
    }

    public function testGetSuggestedActionsAutoGenerated(): void
    {
        $criticalEvent = new CountDiscrepancyEvent(
            $this->countTask,
            ['amount' => 2000]
        );
        $this->assertSame(
            ['immediate_recount', 'manager_review', 'audit_investigation'],
            $criticalEvent->getSuggestedActions()
        );

        $highEvent = new CountDiscrepancyEvent(
            $this->countTask,
            ['amount' => 500]
        );
        $this->assertSame(
            ['supervisor_approval', 'detailed_recount'],
            $highEvent->getSuggestedActions()
        );

        $damageEvent = new CountDiscrepancyEvent(
            $this->countTask,
            ['type' => 'damage', 'amount' => 50]
        );
        $this->assertSame(
            ['damage_assessment', 'insurance_claim'],
            $damageEvent->getSuggestedActions()
        );

        $normalEvent = new CountDiscrepancyEvent(
            $this->countTask,
            ['amount' => 25]
        );
        $this->assertSame(['auto_adjustment'], $normalEvent->getSuggestedActions());
    }

    public function testComplexDiscrepancyScenario(): void
    {
        $discrepancyData = [
            'type' => 'theft_suspected',
            'system_quantity' => 500,
            'actual_quantity' => 450,
            'amount' => 1250.00,
            'severity' => 'critical',
            'requires_approval' => true,
            'location' => 'High-Value-Zone-A',
            'product_sku' => 'ELECTRONICS-001',
            'investigation_notes' => 'Multiple high-value items missing',
        ];

        $context = [
            'operator_id' => 301,
            'supervisor_id' => 401,
            'count_session' => 'CS20250904001',
            'timestamp' => '2025-09-04T22:15:00Z',
        ];

        $event = new CountDiscrepancyEvent($this->countTask, $discrepancyData, $context);

        $this->assertSame('theft_suspected', $event->getDiscrepancyType());
        $this->assertSame('critical', $event->getDiscrepancySeverity());
        $this->assertSame(1250.00, $event->getDiscrepancyAmount());
        $this->assertSame(500, $event->getSystemQuantity());
        $this->assertSame(450, $event->getActualQuantity());
        $this->assertSame(-50, $event->getQuantityDifference());
        $this->assertTrue($event->isNegativeDiscrepancy());
        $this->assertFalse($event->isPositiveDiscrepancy());
        $this->assertTrue($event->requiresApproval());
        $this->assertSame(301, $event->getContext()['operator_id']);
        $this->assertSame('ELECTRONICS-001', $event->getDiscrepancyData()['product_sku']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->countTask = new CountTask();
        $this->countTask->setType(TaskType::COUNT);
        $this->countTask->setStatus(TaskStatus::COMPLETED);
        $this->countTask->setPriority(60);
    }
}
