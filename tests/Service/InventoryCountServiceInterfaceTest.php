<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Entity\CountTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Service\InventoryCountServiceInterface;

/**
 * InventoryCountServiceInterface 接口合约测试
 *
 * @internal
 */
#[CoversClass(InventoryCountServiceInterface::class)]
class InventoryCountServiceInterfaceTest extends TestCase
{
    /**
     * 安全获取反射类型名称
     */
    private function getTypeName(?\ReflectionType $type): string
    {
        if (null === $type) {
            return 'mixed';
        }

        if ($type instanceof \ReflectionNamedType) {
            return $type->getName();
        }

        if ($type instanceof \ReflectionUnionType) {
            return implode('|', array_map(
                fn (\ReflectionIntersectionType|\ReflectionNamedType $t) => $t instanceof \ReflectionNamedType ? $t->getName() : (string) $t,
                $type->getTypes()
            ));
        }

        return (string) $type;
    }

    /**
     * 测试接口是否定义了所有必需的方法
     */
    public function testInterfaceDefinesRequiredMethods(): void
    {
        $reflectionClass = new \ReflectionClass(InventoryCountServiceInterface::class);

        $expectedMethods = [
            'generateCountPlan',
            'executeCountTask',
            'handleDiscrepancy',
            'getCountProgress',
            'generateDiscrepancyReport',
            'analyzeCountResults',
            'optimizeCountFrequency',
            'handleCountException',
            'validateCountDataQuality',
        ];

        $actualMethods = array_map(
            fn (\ReflectionMethod $method) => $method->getName(),
            $reflectionClass->getMethods()
        );

        foreach ($expectedMethods as $expectedMethod) {
            $this->assertContainsEquals(
                $expectedMethod,
                $actualMethods,
                "Interface should define method: {$expectedMethod}"
            );
        }

        $this->assertCount(
            count($expectedMethods),
            $actualMethods,
            'Interface should only define expected methods'
        );
    }

    /**
     * 测试 generateCountPlan 方法签名
     */
    public function testGenerateCountPlanMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(InventoryCountServiceInterface::class);
        $method = $reflectionClass->getMethod('generateCountPlan');

        $this->assertTrue($method->isPublic(), 'generateCountPlan should be public');

        $parameters = $method->getParameters();
        $this->assertCount(3, $parameters, 'generateCountPlan should have 3 parameters');

        // 第一个参数：$countType (string)
        $firstParam = $parameters[0];
        $this->assertSame('countType', $firstParam->getName());
        $this->assertTrue($firstParam->hasType());
        $this->assertSame('string', $this->getTypeName($firstParam->getType()));

        // 第二个参数：$criteria (array)
        $secondParam = $parameters[1];
        $this->assertSame('criteria', $secondParam->getName());
        $this->assertTrue($secondParam->hasType());
        $this->assertSame('array', $this->getTypeName($secondParam->getType()));

        // 第三个参数：$planOptions (array with default [])
        $thirdParam = $parameters[2];
        $this->assertSame('planOptions', $thirdParam->getName());
        $this->assertTrue($thirdParam->hasType());
        $this->assertSame('array', $this->getTypeName($thirdParam->getType()));
        $this->assertTrue($thirdParam->isDefaultValueAvailable());
        $this->assertSame([], $thirdParam->getDefaultValue());

        // 返回类型：CountPlan
        $this->assertTrue($method->hasReturnType());
        $this->assertSame('Tourze\WarehouseOperationBundle\Entity\CountPlan', $this->getTypeName($method->getReturnType()));
    }

    /**
     * 测试 executeCountTask 方法签名
     */
    public function testExecuteCountTaskMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(InventoryCountServiceInterface::class);
        $method = $reflectionClass->getMethod('executeCountTask');

        $parameters = $method->getParameters();
        $this->assertCount(3, $parameters, 'executeCountTask should have 3 parameters');

        // 第一个参数：$task (CountTask)
        $firstParam = $parameters[0];
        $this->assertSame('task', $firstParam->getName());
        $this->assertSame('Tourze\WarehouseOperationBundle\Entity\CountTask', $this->getTypeName($firstParam->getType()));

        // 第二个参数：$countData (array)
        $secondParam = $parameters[1];
        $this->assertSame('countData', $secondParam->getName());
        $this->assertSame('array', $this->getTypeName($secondParam->getType()));

        // 第三个参数：$executionContext (array with default [])
        $thirdParam = $parameters[2];
        $this->assertSame('executionContext', $thirdParam->getName());
        $this->assertSame('array', $this->getTypeName($thirdParam->getType()));
        $this->assertTrue($thirdParam->isDefaultValueAvailable());
        $this->assertSame([], $thirdParam->getDefaultValue());

        // 返回类型：array
        $this->assertTrue($method->hasReturnType());
        $this->assertSame('array', $this->getTypeName($method->getReturnType()));
    }

    /**
     * 测试 handleDiscrepancy 方法签名
     */
    public function testHandleDiscrepancyMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(InventoryCountServiceInterface::class);
        $method = $reflectionClass->getMethod('handleDiscrepancy');

        $parameters = $method->getParameters();
        $this->assertCount(3, $parameters, 'handleDiscrepancy should have 3 parameters');

        // 第一个参数：$task (CountTask)
        $firstParam = $parameters[0];
        $this->assertSame('task', $firstParam->getName());
        $this->assertSame('Tourze\WarehouseOperationBundle\Entity\CountTask', $this->getTypeName($firstParam->getType()));

        // 第二个参数：$discrepancyData (array)
        $secondParam = $parameters[1];
        $this->assertSame('discrepancyData', $secondParam->getName());
        $this->assertSame('array', $this->getTypeName($secondParam->getType()));

        // 第三个参数：$handlingOptions (array with default [])
        $thirdParam = $parameters[2];
        $this->assertSame('handlingOptions', $thirdParam->getName());
        $this->assertSame('array', $this->getTypeName($thirdParam->getType()));
        $this->assertTrue($thirdParam->isDefaultValueAvailable());
        $this->assertSame([], $thirdParam->getDefaultValue());

        // 返回类型：array
        $this->assertTrue($method->hasReturnType());
        $this->assertSame('array', $this->getTypeName($method->getReturnType()));
    }

    /**
     * 测试 getCountProgress 方法签名
     */
    public function testGetCountProgressMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(InventoryCountServiceInterface::class);
        $method = $reflectionClass->getMethod('getCountProgress');

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters, 'getCountProgress should have 1 parameter');

        // 参数：$plan (CountPlan)
        $param = $parameters[0];
        $this->assertSame('plan', $param->getName());
        $this->assertSame('Tourze\WarehouseOperationBundle\Entity\CountPlan', $this->getTypeName($param->getType()));

        // 返回类型：array
        $this->assertTrue($method->hasReturnType());
        $this->assertSame('array', $this->getTypeName($method->getReturnType()));
    }

    /**
     * 测试 generateDiscrepancyReport 方法签名
     */
    public function testGenerateDiscrepancyReportMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(InventoryCountServiceInterface::class);
        $method = $reflectionClass->getMethod('generateDiscrepancyReport');

        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters, 'generateDiscrepancyReport should have 2 parameters');

        // 第一个参数：$plan (CountPlan)
        $firstParam = $parameters[0];
        $this->assertSame('plan', $firstParam->getName());
        $this->assertSame('Tourze\WarehouseOperationBundle\Entity\CountPlan', $this->getTypeName($firstParam->getType()));

        // 第二个参数：$reportOptions (array with default [])
        $secondParam = $parameters[1];
        $this->assertSame('reportOptions', $secondParam->getName());
        $this->assertSame('array', $this->getTypeName($secondParam->getType()));
        $this->assertTrue($secondParam->isDefaultValueAvailable());
        $this->assertSame([], $secondParam->getDefaultValue());

        // 返回类型：array
        $this->assertTrue($method->hasReturnType());
        $this->assertSame('array', $this->getTypeName($method->getReturnType()));
    }

    /**
     * 测试 analyzeCountResults 方法签名
     */
    public function testAnalyzeCountResultsMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(InventoryCountServiceInterface::class);
        $method = $reflectionClass->getMethod('analyzeCountResults');

        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters, 'analyzeCountResults should have 2 parameters');

        // 第一个参数：$planIds (array)
        $firstParam = $parameters[0];
        $this->assertSame('planIds', $firstParam->getName());
        $this->assertSame('array', $this->getTypeName($firstParam->getType()));

        // 第二个参数：$analysisParams (array with default [])
        $secondParam = $parameters[1];
        $this->assertSame('analysisParams', $secondParam->getName());
        $this->assertSame('array', $this->getTypeName($secondParam->getType()));
        $this->assertTrue($secondParam->isDefaultValueAvailable());
        $this->assertSame([], $secondParam->getDefaultValue());

        // 返回类型：array
        $this->assertTrue($method->hasReturnType());
        $this->assertSame('array', $this->getTypeName($method->getReturnType()));
    }

    /**
     * 测试 optimizeCountFrequency 方法签名
     */
    public function testOptimizeCountFrequencyMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(InventoryCountServiceInterface::class);
        $method = $reflectionClass->getMethod('optimizeCountFrequency');

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters, 'optimizeCountFrequency should have 1 parameter');

        // 参数：$optimizationCriteria (array)
        $param = $parameters[0];
        $this->assertSame('optimizationCriteria', $param->getName());
        $this->assertSame('array', $this->getTypeName($param->getType()));
        $this->assertFalse($param->isDefaultValueAvailable());

        // 返回类型：array
        $this->assertTrue($method->hasReturnType());
        $this->assertSame('array', $this->getTypeName($method->getReturnType()));
    }

    /**
     * 测试接口完整性和文档完备性
     */
    public function testInterfaceCompletenessAndDocumentation(): void
    {
        $reflectionClass = new \ReflectionClass(InventoryCountServiceInterface::class);

        // 检查接口有文档注释
        $this->assertNotEmpty(
            $reflectionClass->getDocComment(),
            'Interface should have documentation comment'
        );

        // 检查每个方法都有文档注释
        foreach ($reflectionClass->getMethods() as $method) {
            $this->assertNotEmpty(
                $method->getDocComment(),
                "Method {$method->getName()} should have documentation comment"
            );
        }
    }

    /**
     * 集成测试：验证接口可以被正确实例化（通过Mock）
     */
    public function testInterfaceCanBeInstantiatedViaMock(): void
    {
        $mockService = $this->createMock(InventoryCountServiceInterface::class);

        $this->assertInstanceOf(InventoryCountServiceInterface::class, $mockService);

        // 验证核心方法都可以被调用
        $task = new CountTask();
        $task->setStatus(TaskStatus::PENDING);
        $task->setPriority(70);

        $mockService->expects($this->once())
            ->method('executeCountTask')
            ->with($task, [], [])
            ->willReturn([
                'task_status' => 'completed',
                'count_accuracy' => 98.5,
                'discrepancies' => [],
                'next_actions' => [],
                'completion_time' => 900,
            ])
        ;

        $result = $mockService->executeCountTask($task, [], []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('task_status', $result);
        $this->assertArrayHasKey('count_accuracy', $result);
        $this->assertArrayHasKey('discrepancies', $result);
    }

    /**
     * 测试盘点计划相关方法
     */
    public function testCountPlanMethods(): void
    {
        $reflectionClass = new \ReflectionClass(InventoryCountServiceInterface::class);

        // generateCountPlan 应该返回 CountPlan
        $generateMethod = $reflectionClass->getMethod('generateCountPlan');
        $this->assertNotEmpty($generateMethod->getDocComment());
        $this->assertSame('Tourze\WarehouseOperationBundle\Entity\CountPlan', $this->getTypeName($generateMethod->getReturnType()));

        // getCountProgress 应该接收 CountPlan 参数
        $progressMethod = $reflectionClass->getMethod('getCountProgress');
        $params = $progressMethod->getParameters();
        $this->assertSame('Tourze\WarehouseOperationBundle\Entity\CountPlan', $this->getTypeName($params[0]->getType()));
    }

    /**
     * 测试数据质量和异常处理方法
     */
    public function testDataQualityAndExceptionMethods(): void
    {
        $reflectionClass = new \ReflectionClass(InventoryCountServiceInterface::class);

        // validateCountDataQuality 方法检查
        $validateMethod = $reflectionClass->getMethod('validateCountDataQuality');
        $this->assertTrue($validateMethod->isPublic());
        $this->assertNotEmpty($validateMethod->getDocComment());

        // handleCountException 方法检查
        $exceptionMethod = $reflectionClass->getMethod('handleCountException');
        $params = $exceptionMethod->getParameters();
        $this->assertCount(3, $params);
        $this->assertSame('Tourze\WarehouseOperationBundle\Entity\CountTask', $this->getTypeName($params[0]->getType()));
        $this->assertSame('string', $this->getTypeName($params[1]->getType()));
        $this->assertSame('array', $this->getTypeName($params[2]->getType()));
    }
}
