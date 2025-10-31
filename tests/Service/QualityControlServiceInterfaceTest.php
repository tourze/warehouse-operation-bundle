<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Entity\QualityTask;
use Tourze\WarehouseOperationBundle\Enum\TaskStatus;
use Tourze\WarehouseOperationBundle\Service\QualityControlServiceInterface;

/**
 * QualityControlServiceInterface 接口合约测试
 *
 * @internal
 */
#[CoversClass(QualityControlServiceInterface::class)]
class QualityControlServiceInterfaceTest extends TestCase
{
    /**
     * 测试接口是否定义了所有必需的方法
     */
    public function testInterfaceDefinesRequiredMethods(): void
    {
        $reflectionClass = new \ReflectionClass(QualityControlServiceInterface::class);

        $expectedMethods = [
            'performQualityCheck',
            'handleQualityFailure',
            'getApplicableStandards',
            'validateQualityStandard',
            'generateQualityReport',
            'analyzeQualityStatistics',
            'executeSampleInspection',
            'escalateQualityIssue',
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
     * 测试 performQualityCheck 方法签名
     */
    public function testPerformQualityCheckMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(QualityControlServiceInterface::class);
        $method = $reflectionClass->getMethod('performQualityCheck');

        $this->assertTrue($method->isPublic(), 'performQualityCheck should be public');

        $parameters = $method->getParameters();
        $this->assertCount(3, $parameters, 'performQualityCheck should have 3 parameters');

        // 第一个参数：$task (QualityTask)
        $firstParam = $parameters[0];
        $this->assertSame('task', $firstParam->getName());
        $this->assertTrue($firstParam->hasType());
        $type = $firstParam->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('Tourze\WarehouseOperationBundle\Entity\QualityTask', $type->getName());

        // 第二个参数：$checkData (array)
        $secondParam = $parameters[1];
        $this->assertSame('checkData', $secondParam->getName());
        $this->assertTrue($secondParam->hasType());
        $type = $secondParam->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('array', $type->getName());

        // 第三个参数：$options (array with default [])
        $thirdParam = $parameters[2];
        $this->assertSame('options', $thirdParam->getName());
        $this->assertTrue($thirdParam->hasType());
        $type = $thirdParam->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('array', $type->getName());
        $this->assertTrue($thirdParam->isDefaultValueAvailable());
        $this->assertSame([], $thirdParam->getDefaultValue());

        // 返回类型：array
        $this->assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        $this->assertSame('array', $returnType->getName());
    }

    /**
     * 测试 handleQualityFailure 方法签名
     */
    public function testHandleQualityFailureMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(QualityControlServiceInterface::class);
        $method = $reflectionClass->getMethod('handleQualityFailure');

        $parameters = $method->getParameters();
        $this->assertCount(4, $parameters, 'handleQualityFailure should have 4 parameters');

        // 第一个参数：$task (QualityTask)
        $firstParam = $parameters[0];
        $this->assertSame('task', $firstParam->getName());
        $type = $firstParam->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('Tourze\WarehouseOperationBundle\Entity\QualityTask', $type->getName());

        // 第二个参数：$failureReason (string)
        $secondParam = $parameters[1];
        $this->assertSame('failureReason', $secondParam->getName());
        $type = $secondParam->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('string', $type->getName());

        // 第三个参数：$failureDetails (array with default [])
        $thirdParam = $parameters[2];
        $this->assertSame('failureDetails', $thirdParam->getName());
        $type = $thirdParam->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('array', $type->getName());
        $this->assertTrue($thirdParam->isDefaultValueAvailable());
        $this->assertSame([], $thirdParam->getDefaultValue());

        // 第四个参数：$handlingOptions (array with default [])
        $fourthParam = $parameters[3];
        $this->assertSame('handlingOptions', $fourthParam->getName());
        $type = $fourthParam->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('array', $type->getName());
        $this->assertTrue($fourthParam->isDefaultValueAvailable());
        $this->assertSame([], $fourthParam->getDefaultValue());

        // 返回类型：array
        $this->assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        $this->assertSame('array', $returnType->getName());
    }

    /**
     * 测试 getApplicableStandards 方法签名
     */
    public function testGetApplicableStandardsMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(QualityControlServiceInterface::class);
        $method = $reflectionClass->getMethod('getApplicableStandards');

        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters, 'getApplicableStandards should have 2 parameters');

        // 第一个参数：$productAttributes (array)
        $firstParam = $parameters[0];
        $this->assertSame('productAttributes', $firstParam->getName());
        $type = $firstParam->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('array', $type->getName());

        // 第二个参数：$context (array with default [])
        $secondParam = $parameters[1];
        $this->assertSame('context', $secondParam->getName());
        $type = $secondParam->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('array', $type->getName());
        $this->assertTrue($secondParam->isDefaultValueAvailable());
        $this->assertSame([], $secondParam->getDefaultValue());

        // 返回类型：array
        $this->assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        $this->assertSame('array', $returnType->getName());
    }

    /**
     * 测试 validateQualityStandard 方法签名
     */
    public function testValidateQualityStandardMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(QualityControlServiceInterface::class);
        $method = $reflectionClass->getMethod('validateQualityStandard');

        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters, 'validateQualityStandard should have 2 parameters');

        // 第一个参数：$standard (QualityStandard)
        $firstParam = $parameters[0];
        $this->assertSame('standard', $firstParam->getName());
        $type = $firstParam->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('Tourze\WarehouseOperationBundle\Entity\QualityStandard', $type->getName());

        // 第二个参数：$validationContext (array with default [])
        $secondParam = $parameters[1];
        $this->assertSame('validationContext', $secondParam->getName());
        $type = $secondParam->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('array', $type->getName());
        $this->assertTrue($secondParam->isDefaultValueAvailable());
        $this->assertSame([], $secondParam->getDefaultValue());

        // 返回类型：array
        $this->assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        $this->assertSame('array', $returnType->getName());
    }

    /**
     * 测试 generateQualityReport 方法签名
     */
    public function testGenerateQualityReportMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(QualityControlServiceInterface::class);
        $method = $reflectionClass->getMethod('generateQualityReport');

        $parameters = $method->getParameters();
        $this->assertCount(2, $parameters, 'generateQualityReport should have 2 parameters');

        // 第一个参数：$taskIds (array)
        $firstParam = $parameters[0];
        $this->assertSame('taskIds', $firstParam->getName());
        $type = $firstParam->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('array', $type->getName());

        // 第二个参数：$reportOptions (array with default [])
        $secondParam = $parameters[1];
        $this->assertSame('reportOptions', $secondParam->getName());
        $type = $secondParam->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('array', $type->getName());
        $this->assertTrue($secondParam->isDefaultValueAvailable());
        $this->assertSame([], $secondParam->getDefaultValue());

        // 返回类型：array
        $this->assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        $this->assertSame('array', $returnType->getName());
    }

    /**
     * 测试 analyzeQualityStatistics 方法签名
     */
    public function testAnalyzeQualityStatisticsMethodSignature(): void
    {
        $reflectionClass = new \ReflectionClass(QualityControlServiceInterface::class);
        $method = $reflectionClass->getMethod('analyzeQualityStatistics');

        $parameters = $method->getParameters();
        $this->assertCount(1, $parameters, 'analyzeQualityStatistics should have 1 parameter');

        // 参数：$analysisParams (array with default [])
        $param = $parameters[0];
        $this->assertSame('analysisParams', $param->getName());
        $type = $param->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('array', $type->getName());
        $this->assertTrue($param->isDefaultValueAvailable());
        $this->assertSame([], $param->getDefaultValue());

        // 返回类型：array
        $this->assertTrue($method->hasReturnType());
        $returnType = $method->getReturnType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        $this->assertSame('array', $returnType->getName());
    }

    /**
     * 测试接口完整性和文档完备性
     */
    public function testInterfaceCompletenessAndDocumentation(): void
    {
        $reflectionClass = new \ReflectionClass(QualityControlServiceInterface::class);

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
        $mockService = $this->createMock(QualityControlServiceInterface::class);

        $this->assertInstanceOf(QualityControlServiceInterface::class, $mockService);

        // 验证核心方法都可以被调用
        $task = new QualityTask();
        $task->setStatus(TaskStatus::PENDING);
        $task->setPriority(80);

        $mockService->expects($this->once())
            ->method('performQualityCheck')
            ->with($task, [], [])
            ->willReturn([
                'overall_result' => 'pass',
                'check_results' => [],
                'quality_score' => 95,
                'defects' => [],
                'recommendations' => [],
                'inspector_notes' => '',
                'photos' => [],
            ])
        ;

        $result = $mockService->performQualityCheck($task, [], []);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_result', $result);
        $this->assertArrayHasKey('quality_score', $result);
    }

    /**
     * 测试质检标准相关方法
     */
    public function testQualityStandardMethods(): void
    {
        $reflectionClass = new \ReflectionClass(QualityControlServiceInterface::class);

        // getApplicableStandards 应该返回 QualityStandard[]
        $getStandardsMethod = $reflectionClass->getMethod('getApplicableStandards');
        $this->assertNotEmpty($getStandardsMethod->getDocComment());

        // validateQualityStandard 应该接收 QualityStandard 参数
        $validateMethod = $reflectionClass->getMethod('validateQualityStandard');
        $params = $validateMethod->getParameters();
        $type = $params[0]->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('Tourze\WarehouseOperationBundle\Entity\QualityStandard', $type->getName());
    }
}
