<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service\Scheduling;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerLoadBalancer;

/**
 * WorkerLoadBalancer 单元测试
 *
 * 测试作业员负载均衡服务的功能，包括作业员筛选、工作量得分计算等核心逻辑。
 * @internal
 */
#[CoversClass(WorkerLoadBalancer::class)]
class WorkerLoadBalancerTest extends TestCase
{
    private WorkerLoadBalancer $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WorkerLoadBalancer();
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerLoadBalancer::filterEligibleWorkers
     */
    public function testFilterEligibleWorkersWithAvailableWorkers(): void
    {
        $availableWorkers = [
            1 => ['worker_id' => 1, 'current_workload' => 5, 'availability' => 'available'],
            2 => ['worker_id' => 2, 'current_workload' => 8, 'availability' => 'available'],
            3 => ['worker_id' => 3, 'current_workload' => 3, 'availability' => 'available'],
        ];

        $constraints = ['max_tasks_per_worker' => 10];

        $result = $this->service->filterEligibleWorkers($availableWorkers, $constraints);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
        $this->assertArrayHasKey(3, $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerLoadBalancer::filterEligibleWorkers
     */
    public function testFilterEligibleWorkersFiltersOverloadedWorkers(): void
    {
        $availableWorkers = [
            1 => ['worker_id' => 1, 'current_workload' => 5, 'availability' => 'available'],
            2 => ['worker_id' => 2, 'current_workload' => 12, 'availability' => 'available'], // 超载
            3 => ['worker_id' => 3, 'current_workload' => 3, 'availability' => 'available'],
        ];

        $constraints = ['max_tasks_per_worker' => 10];

        $result = $this->service->filterEligibleWorkers($availableWorkers, $constraints);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(3, $result);
        $this->assertArrayNotHasKey(2, $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerLoadBalancer::filterEligibleWorkers
     */
    public function testFilterEligibleWorkersFiltersUnavailableWorkers(): void
    {
        $availableWorkers = [
            1 => ['worker_id' => 1, 'current_workload' => 5, 'availability' => 'available'],
            2 => ['worker_id' => 2, 'current_workload' => 3, 'availability' => 'busy'], // 不可用
            3 => ['worker_id' => 3, 'current_workload' => 2, 'availability' => 'available'],
        ];

        $constraints = ['max_tasks_per_worker' => 10];

        $result = $this->service->filterEligibleWorkers($availableWorkers, $constraints);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(3, $result);
        $this->assertArrayNotHasKey(2, $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerLoadBalancer::filterEligibleWorkers
     */
    public function testFilterEligibleWorkersWithEmptyWorkers(): void
    {
        $availableWorkers = [];
        $constraints = ['max_tasks_per_worker' => 10];

        $result = $this->service->filterEligibleWorkers($availableWorkers, $constraints);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerLoadBalancer::filterEligibleWorkers
     */
    public function testFilterEligibleWorkersWithDefaultMaxTasks(): void
    {
        $availableWorkers = [
            1 => ['worker_id' => 1, 'current_workload' => 5, 'availability' => 'available'],
            2 => ['worker_id' => 2, 'current_workload' => 11, 'availability' => 'available'],
        ];

        // 不设置max_tasks_per_worker，应该使用默认值10
        $constraints = [];

        $result = $this->service->filterEligibleWorkers($availableWorkers, $constraints);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey(1, $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerLoadBalancer::calculateWorkloadScore
     */
    public function testCalculateWorkloadScoreWithLowWorkload(): void
    {
        $score = $this->service->calculateWorkloadScore(2);

        $this->assertIsFloat($score);
        $this->assertGreaterThan(0.5, $score);
        $this->assertLessThanOrEqual(1.0, $score);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerLoadBalancer::calculateWorkloadScore
     */
    public function testCalculateWorkloadScoreWithHighWorkload(): void
    {
        $score = $this->service->calculateWorkloadScore(8);

        $this->assertIsFloat($score);
        $this->assertLessThan(0.5, $score);
        $this->assertGreaterThanOrEqual(0.0, $score);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerLoadBalancer::calculateWorkloadScore
     */
    public function testCalculateWorkloadScoreWithZeroWorkload(): void
    {
        $score = $this->service->calculateWorkloadScore(0);

        $this->assertEquals(1.0, $score);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerLoadBalancer::calculateWorkloadScore
     */
    public function testCalculateWorkloadScoreWithMaxWorkload(): void
    {
        $score = $this->service->calculateWorkloadScore(10);

        $this->assertEquals(0.0, $score);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerLoadBalancer::calculateWorkloadScore
     */
    public function testCalculateWorkloadScoreWithOverload(): void
    {
        $score = $this->service->calculateWorkloadScore(15);

        // 超载时分数应该小于等于0
        $this->assertLessThanOrEqual(0.0, $score);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerLoadBalancer::calculateWorkloadScore
     */
    public function testCalculateWorkloadScoreDecreases(): void
    {
        $score1 = $this->service->calculateWorkloadScore(2);
        $score2 = $this->service->calculateWorkloadScore(5);
        $score3 = $this->service->calculateWorkloadScore(8);

        // 工作量增加时，得分应该递减
        $this->assertGreaterThan($score2, $score1);
        $this->assertGreaterThan($score3, $score2);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerLoadBalancer::filterEligibleWorkers
     */
    public function testFilterEligibleWorkersWithInvalidWorkload(): void
    {
        $availableWorkers = [
            1 => ['worker_id' => 1, 'current_workload' => 'invalid', 'availability' => 'available'],
            2 => ['worker_id' => 2, 'current_workload' => 5, 'availability' => 'available'],
        ];

        $constraints = ['max_tasks_per_worker' => 10];

        $result = $this->service->filterEligibleWorkers($availableWorkers, $constraints);

        // 无效工作量应该被处理为0，因此worker 1应该被包含
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerLoadBalancer::filterEligibleWorkers
     */
    public function testFilterEligibleWorkersWithMissingWorkload(): void
    {
        $availableWorkers = [
            1 => ['worker_id' => 1, 'availability' => 'available'], // 缺少current_workload
            2 => ['worker_id' => 2, 'current_workload' => 5, 'availability' => 'available'],
        ];

        $constraints = ['max_tasks_per_worker' => 10];

        $result = $this->service->filterEligibleWorkers($availableWorkers, $constraints);

        // 缺少工作量应该被处理为0，因此worker 1应该被包含
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerLoadBalancer::filterEligibleWorkers
     */
    public function testFilterEligibleWorkersWithMissingAvailability(): void
    {
        $availableWorkers = [
            1 => ['worker_id' => 1, 'current_workload' => 5], // 缺少availability
            2 => ['worker_id' => 2, 'current_workload' => 3, 'availability' => 'available'],
        ];

        $constraints = ['max_tasks_per_worker' => 10];

        $result = $this->service->filterEligibleWorkers($availableWorkers, $constraints);

        // 缺少availability应该被排除
        $this->assertArrayNotHasKey(1, $result);
        $this->assertArrayHasKey(2, $result);
    }

    /**
     * @covers \Tourze\WarehouseOperationBundle\Service\Scheduling\WorkerLoadBalancer::filterEligibleWorkers
     */
    public function testFilterEligibleWorkersWithInvalidMaxTasks(): void
    {
        $availableWorkers = [
            1 => ['worker_id' => 1, 'current_workload' => 5, 'availability' => 'available'],
            2 => ['worker_id' => 2, 'current_workload' => 8, 'availability' => 'available'],
        ];

        $constraints = ['max_tasks_per_worker' => 'invalid'];

        $result = $this->service->filterEligibleWorkers($availableWorkers, $constraints);

        // 无效的max_tasks_per_worker应该使用默认值10
        $this->assertCount(2, $result);
    }

    public function testServiceConstructorAndBasicFunctionality(): void
    {
        // 验证服务可以正确实例化
        $this->assertInstanceOf(WorkerLoadBalancer::class, $this->service);

        // 验证基本功能工作正常
        $availableWorkers = [
            1 => ['worker_id' => 1, 'current_workload' => 5, 'availability' => 'available'],
        ];

        $result = $this->service->filterEligibleWorkers($availableWorkers, []);
        $this->assertIsArray($result);

        $score = $this->service->calculateWorkloadScore(5);
        $this->assertIsFloat($score);
    }
}
