<?php

namespace Tourze\WarehouseOperationBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\WarehouseOperationBundle\DependencyInjection\WarehouseOperationExtension;

/**
 * @internal
 */
#[CoversClass(WarehouseOperationExtension::class)]
class WarehouseOperationExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    protected function createExtension(): WarehouseOperationExtension
    {
        return new WarehouseOperationExtension();
    }

    protected function getExpectedAlias(): string
    {
        return 'warehouse_operation';
    }

    /**
     * @return array<string>
     */
    protected function getExpectedConfigKeys(): array
    {
        return [];
    }

    /**
     * 跳过 WithMonologChannel 检查，因为属性类不存在
     * @return array<string>
     */
    protected function getLoggerServicesWithoutMonologChannel(): array
    {
        return [
            'Tourze\WarehouseOperationBundle\Service\Scheduling\TaskPriorityCalculatorService',
            'Tourze\WarehouseOperationBundle\Service\WorkflowOrchestrationService',
            'Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerAssignmentService',
            'Tourze\WarehouseOperationBundle\Service\TaskManager',
            'Tourze\WarehouseOperationBundle\Service\TaskSchedulingService',
        ];
    }
}
