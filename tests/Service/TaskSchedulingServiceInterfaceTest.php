<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Service\TaskSchedulingServiceInterface;

/**
 * TaskSchedulingServiceInterface 接口合约测试
 *
 * 测试接口定义是否符合预期的方法签名和行为规范。
 * 这是一个合约测试，确保接口实现的一致性。
 *
 * @internal
 */
#[CoversClass(TaskSchedulingServiceInterface::class)]
class TaskSchedulingServiceInterfaceTest extends TestCase
{
    /**
     * 测试接口是否定义了所有必需的方法
     */
    public function testInterfaceDefinesRequiredMethods(): void
    {
        $reflectionClass = new \ReflectionClass(TaskSchedulingServiceInterface::class);

        $expectedMethods = [
            'scheduleTaskBatch',
            'recalculatePriorities',
            'assignWorkerBySkill',
            'getSchedulingQueueStatus',
            'analyzeSchedulingOptimization',
            'handleUrgentTaskInsertion',
            'batchReassignTasks',
        ];

        $actualMethods = array_map(
            fn (\ReflectionMethod $method) => $method->getName(),
            $reflectionClass->getMethods()
        );

        foreach ($expectedMethods as $expectedMethod) {
            self::assertContainsEquals(
                $expectedMethod,
                $actualMethods,
                "Interface should define method: {$expectedMethod}"
            );
        }

        self::assertCount(
            count($expectedMethods),
            $actualMethods,
            'Interface should only define expected methods'
        );
    }

    /**
     * 测试 scheduleTaskBatch 方法签名
     */
    public function testScheduleTaskBatchMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(TaskSchedulingServiceInterface::class);
        $method = $reflectionClass->getMethod('scheduleTaskBatch');

        self::assertTrue($method->isPublic(), 'scheduleTaskBatch should be public');

        $parameters = $method->getParameters();
        self::assertCount(2, $parameters, 'scheduleTaskBatch should have 2 parameters');

        // 第一个参数：$pendingTasks (array)
        $firstParam = $parameters[0];
        self::assertSame('pendingTasks', $firstParam->getName());
        self::assertTrue($firstParam->hasType());
        $firstParamType = $firstParam->getType();
        self::assertInstanceOf(\ReflectionNamedType::class, $firstParamType);
        self::assertSame('array', $firstParamType->getName());

        // 第二个参数：$constraints (array with default [])
        $secondParam = $parameters[1];
        self::assertSame('constraints', $secondParam->getName());
        self::assertTrue($secondParam->hasType());
        $secondParamType = $secondParam->getType();
        self::assertInstanceOf(\ReflectionNamedType::class, $secondParamType);
        self::assertSame('array', $secondParamType->getName());
        self::assertTrue($secondParam->isDefaultValueAvailable());
        self::assertSame([], $secondParam->getDefaultValue());

        // 返回类型：array
        self::assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        self::assertInstanceOf(\ReflectionNamedType::class, $returnType);
        self::assertSame('array', $returnType->getName());
    }

    /**
     * 测试 recalculatePriorities 方法签名
     */
    public function testRecalculatePrioritiesMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(TaskSchedulingServiceInterface::class);
        $method = $reflectionClass->getMethod('recalculatePriorities');

        self::assertTrue($method->isPublic(), 'recalculatePriorities should be public');

        $parameters = $method->getParameters();
        self::assertCount(1, $parameters, 'recalculatePriorities should have 1 parameter');

        // 参数：$context (array with default [])
        $param = $parameters[0];
        self::assertSame('context', $param->getName());
        self::assertTrue($param->hasType());
        $paramType = $param->getType();
        self::assertInstanceOf(\ReflectionNamedType::class, $paramType);
        self::assertSame('array', $paramType->getName());
        self::assertTrue($param->isDefaultValueAvailable());
        self::assertSame([], $param->getDefaultValue());

        // 返回类型：array
        self::assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        self::assertInstanceOf(\ReflectionNamedType::class, $returnType);
        self::assertSame('array', $returnType->getName());
    }

    /**
     * 测试 assignWorkerBySkill 方法签名
     */
    public function testAssignWorkerBySkillMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(TaskSchedulingServiceInterface::class);
        $method = $reflectionClass->getMethod('assignWorkerBySkill');

        self::assertTrue($method->isPublic(), 'assignWorkerBySkill should be public');

        $parameters = $method->getParameters();
        self::assertCount(2, $parameters, 'assignWorkerBySkill should have 2 parameters');

        // 第一个参数：$task (WarehouseTask)
        $firstParam = $parameters[0];
        self::assertSame('task', $firstParam->getName());
        self::assertTrue($firstParam->hasType());
        $firstParamType = $firstParam->getType();
        self::assertInstanceOf(\ReflectionNamedType::class, $firstParamType);
        self::assertSame('Tourze\WarehouseOperationBundle\Entity\WarehouseTask', $firstParamType->getName());

        // 第二个参数：$options (array with default [])
        $secondParam = $parameters[1];
        self::assertSame('options', $secondParam->getName());
        self::assertTrue($secondParam->hasType());
        $secondParamType = $secondParam->getType();
        self::assertInstanceOf(\ReflectionNamedType::class, $secondParamType);
        self::assertSame('array', $secondParamType->getName());
        self::assertTrue($secondParam->isDefaultValueAvailable());
        self::assertSame([], $secondParam->getDefaultValue());

        // 返回类型：?array (nullable array)
        self::assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();

        // PHP 8.0+ nullable types can be either ReflectionNamedType or ReflectionUnionType
        if ($returnType instanceof \ReflectionNamedType) {
            self::assertTrue($returnType->allowsNull());
            self::assertSame('array', $returnType->getName());
        } elseif ($returnType instanceof \ReflectionUnionType) {
            self::assertTrue($returnType->allowsNull());
        } else {
            self::fail('Unexpected return type: ' . (null !== $returnType ? get_class($returnType) : 'null'));
        }
    }

    /**
     * 测试 getSchedulingQueueStatus 方法签名
     */
    public function testGetSchedulingQueueStatusMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(TaskSchedulingServiceInterface::class);
        $method = $reflectionClass->getMethod('getSchedulingQueueStatus');

        self::assertTrue($method->isPublic(), 'getSchedulingQueueStatus should be public');
        self::assertCount(0, $method->getParameters(), 'getSchedulingQueueStatus should have no parameters');

        // 返回类型：array
        self::assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        self::assertInstanceOf(\ReflectionNamedType::class, $returnType);
        self::assertSame('array', $returnType->getName());
    }

    /**
     * 测试 analyzeSchedulingOptimization 方法签名
     */
    public function testAnalyzeSchedulingOptimizationMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(TaskSchedulingServiceInterface::class);
        $method = $reflectionClass->getMethod('analyzeSchedulingOptimization');

        self::assertTrue($method->isPublic(), 'analyzeSchedulingOptimization should be public');

        $parameters = $method->getParameters();
        self::assertCount(1, $parameters, 'analyzeSchedulingOptimization should have 1 parameter');

        // 参数：$criteria (array with default [])
        $param = $parameters[0];
        self::assertSame('criteria', $param->getName());
        self::assertTrue($param->hasType());
        $paramType = $param->getType();
        self::assertInstanceOf(\ReflectionNamedType::class, $paramType);
        self::assertSame('array', $paramType->getName());
        self::assertTrue($param->isDefaultValueAvailable());
        self::assertSame([], $param->getDefaultValue());

        // 返回类型：array
        self::assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        self::assertInstanceOf(\ReflectionNamedType::class, $returnType);
        self::assertSame('array', $returnType->getName());
    }

    /**
     * 测试 handleUrgentTaskInsertion 方法签名
     */
    public function testHandleUrgentTaskInsertionMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(TaskSchedulingServiceInterface::class);
        $method = $reflectionClass->getMethod('handleUrgentTaskInsertion');

        self::assertTrue($method->isPublic(), 'handleUrgentTaskInsertion should be public');

        $parameters = $method->getParameters();
        self::assertCount(2, $parameters, 'handleUrgentTaskInsertion should have 2 parameters');

        // 第一个参数：$urgentTask (WarehouseTask)
        $firstParam = $parameters[0];
        self::assertSame('urgentTask', $firstParam->getName());
        self::assertTrue($firstParam->hasType());
        $firstParamType = $firstParam->getType();
        self::assertInstanceOf(\ReflectionNamedType::class, $firstParamType);
        self::assertSame('Tourze\WarehouseOperationBundle\Entity\WarehouseTask', $firstParamType->getName());

        // 第二个参数：$urgencyLevel (array)
        $secondParam = $parameters[1];
        self::assertSame('urgencyLevel', $secondParam->getName());
        self::assertTrue($secondParam->hasType());
        $secondParamType = $secondParam->getType();
        self::assertInstanceOf(\ReflectionNamedType::class, $secondParamType);
        self::assertSame('array', $secondParamType->getName());
        self::assertFalse($secondParam->isDefaultValueAvailable());

        // 返回类型：array
        self::assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        self::assertInstanceOf(\ReflectionNamedType::class, $returnType);
        self::assertSame('array', $returnType->getName());
    }

    /**
     * 测试 batchReassignTasks 方法签名
     */
    public function testBatchReassignTasksMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(TaskSchedulingServiceInterface::class);
        $method = $reflectionClass->getMethod('batchReassignTasks');

        self::assertTrue($method->isPublic(), 'batchReassignTasks should be public');

        $parameters = $method->getParameters();
        self::assertCount(3, $parameters, 'batchReassignTasks should have 3 parameters');

        // 第一个参数：$affectedTaskIds (array)
        $firstParam = $parameters[0];
        self::assertSame('affectedTaskIds', $firstParam->getName());
        self::assertTrue($firstParam->hasType());
        $firstParamType = $firstParam->getType();
        self::assertInstanceOf(\ReflectionNamedType::class, $firstParamType);
        self::assertSame('array', $firstParamType->getName());

        // 第二个参数：$reason (string)
        $secondParam = $parameters[1];
        self::assertSame('reason', $secondParam->getName());
        self::assertTrue($secondParam->hasType());
        $secondParamType = $secondParam->getType();
        self::assertInstanceOf(\ReflectionNamedType::class, $secondParamType);
        self::assertSame('string', $secondParamType->getName());
        self::assertFalse($secondParam->isDefaultValueAvailable());

        // 第三个参数：$constraints (array with default [])
        $thirdParam = $parameters[2];
        self::assertSame('constraints', $thirdParam->getName());
        self::assertTrue($thirdParam->hasType());
        $thirdParamType = $thirdParam->getType();
        self::assertInstanceOf(\ReflectionNamedType::class, $thirdParamType);
        self::assertSame('array', $thirdParamType->getName());
        self::assertTrue($thirdParam->isDefaultValueAvailable());
        self::assertSame([], $thirdParam->getDefaultValue());

        // 返回类型：array
        self::assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        self::assertInstanceOf(\ReflectionNamedType::class, $returnType);
        self::assertSame('array', $returnType->getName());
    }

    /**
     * 测试接口完整性和文档完备性
     */
    public function testInterfaceCompletenessAndDocumentation(): void
    {
        $reflectionClass = new \ReflectionClass(TaskSchedulingServiceInterface::class);

        // 检查接口有文档注释
        self::assertNotEmpty(
            $reflectionClass->getDocComment(),
            'Interface should have documentation comment'
        );

        // 检查每个方法都有文档注释
        foreach ($reflectionClass->getMethods() as $method) {
            self::assertNotEmpty(
                $method->getDocComment(),
                "Method {$method->getName()} should have documentation comment"
            );
        }
    }

    /**
     * 测试接口是否在正确的命名空间中
     */
    public function testInterfaceNamespace(): void
    {
        $reflectionClass = new \ReflectionClass(TaskSchedulingServiceInterface::class);

        self::assertSame(
            'Tourze\WarehouseOperationBundle\Service',
            $reflectionClass->getNamespaceName(),
            'Interface should be in the correct namespace'
        );
    }

    /**
     * 集成测试：验证接口可以被正确实例化（通过Mock）
     */
    public function testInterfaceCanBeInstantiatedViaMock(): void
    {
        $mockService = $this->createMock(TaskSchedulingServiceInterface::class);

        self::assertInstanceOf(TaskSchedulingServiceInterface::class, $mockService);

        // 验证所有方法都可以被调用（通过期望设置）
        $task = new InboundTask();
        $task->setStatus(TaskStatus::PENDING);
        $task->setPriority(50);

        $mockService->expects($this->once())
            ->method('scheduleTaskBatch')
            ->with([$task], [])
            ->willReturn([
                'assignments' => [],
                'unassigned' => [],
                'statistics' => [],
                'recommendations' => [],
            ])
        ;

        $result = $mockService->scheduleTaskBatch([$task], []);

        self::assertIsArray($result);
        self::assertArrayHasKey('assignments', $result);
        self::assertArrayHasKey('unassigned', $result);
        self::assertArrayHasKey('statistics', $result);
        self::assertArrayHasKey('recommendations', $result);
    }
}
