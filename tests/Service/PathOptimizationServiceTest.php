<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Entity\Location;
use Tourze\WarehouseOperationBundle\Entity\Shelf;
use Tourze\WarehouseOperationBundle\Entity\Warehouse;
use Tourze\WarehouseOperationBundle\Entity\Zone;
use Tourze\WarehouseOperationBundle\Service\PathOptimizationService;

/**
 * @group warehouse-operation
 * @group service
 * @group path-optimization
 * @internal
 */
#[CoversClass(PathOptimizationService::class)]
#[RunTestsInSeparateProcesses]
class PathOptimizationServiceTest extends AbstractIntegrationTestCase
{
    private PathOptimizationService $service;

    protected function onSetUp(): void
    {
        $this->service = parent::getService(PathOptimizationService::class);
    }

    public function testOptimizePathWithEmptyLocations(): void
    {
        $result = $this->service->optimizePath([]);

        $this->assertIsArray($result);
        $this->assertSame([], $result['optimized_sequence']);
        $this->assertSame(0.0, $result['total_distance']);
        $this->assertSame(0.0, $result['estimated_time']);
        $this->assertSame(0.0, $result['efficiency_improvement']);
    }

    public function testOptimizePathWithSingleLocation(): void
    {
        $location = $this->createMockLocation(1, 1, 1);
        $locations = [$location];

        $result = $this->service->optimizePath($locations);

        /** @var Location[] $optimizedSequence */
        $optimizedSequence = $result['optimized_sequence'];
        $this->assertCount(1, $optimizedSequence);
        $this->assertIsArray($optimizedSequence);
        $this->assertSame($location, $optimizedSequence[0]);
        $this->assertSame(0.0, $result['total_distance']);
        $this->assertSame(0.0, $result['estimated_time']);
        $this->assertSame(0.0, $result['efficiency_improvement']);
    }

    public function testOptimizePathShortestStrategy(): void
    {
        // 创建测试位置：同一Zone内不同Shelf的位置
        $locations = [
            $this->createMockLocation(1, 1, 1), // Zone1, Shelf1
            $this->createMockLocation(2, 3, 1), // Zone1, Shelf3
            $this->createMockLocation(3, 2, 1), // Zone1, Shelf2
        ];

        $result = $this->service->optimizePath($locations, 'shortest');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('optimized_sequence', $result);
        $this->assertArrayHasKey('total_distance', $result);
        $this->assertArrayHasKey('estimated_time', $result);
        $this->assertArrayHasKey('efficiency_improvement', $result);

        /** @var Location[] $optimizedSequence */
        $optimizedSequence = $result['optimized_sequence'];
        $this->assertCount(3, $optimizedSequence);
        $this->assertIsFloat($result['total_distance']);
        $this->assertIsFloat($result['estimated_time']);
        $this->assertIsFloat($result['efficiency_improvement']);
    }

    public function testOptimizePathSShapeStrategy(): void
    {
        // 创建测试位置：跨Zone的位置
        $locations = [
            $this->createMockLocation(1, 1, 1), // Zone1, Shelf1
            $this->createMockLocation(2, 1, 2), // Zone2, Shelf1
            $this->createMockLocation(3, 2, 1), // Zone1, Shelf2
            $this->createMockLocation(4, 2, 2), // Zone2, Shelf2
        ];

        $result = $this->service->optimizePath($locations, 's_shape');

        /** @var Location[] $sequence */
        $sequence = $result['optimized_sequence'];
        $this->assertCount(4, $sequence);
        $this->assertGreaterThan(0, $result['total_distance']);

        // S型策略应该按Zone分组
        $this->assertIsArray($sequence);
        $this->assertInstanceOf(Location::class, $sequence[0]);
    }

    public function testOptimizePathZShapeStrategy(): void
    {
        // 创建测试位置：同一Zone内多个Shelf
        $locations = [
            $this->createMockLocation(3, 2, 1), // Zone1, Shelf2, Location3
            $this->createMockLocation(1, 1, 1), // Zone1, Shelf1, Location1
            $this->createMockLocation(4, 2, 1), // Zone1, Shelf2, Location4
            $this->createMockLocation(2, 1, 1), // Zone1, Shelf1, Location2
        ];

        $result = $this->service->optimizePath($locations, 'z_shape');

        // Z型策略应该按Shelf分组并且同一Shelf内按ID排序
        /** @var Location[] $sequence */
        $sequence = $result['optimized_sequence'];
        $this->assertCount(4, $sequence);
        $this->assertIsArray($sequence);
        $shelf1Locations = [];
        $shelf2Locations = [];

        foreach ($sequence as $location) {
            $this->assertInstanceOf(Location::class, $location);
            $shelf = $location->getShelf();
            $this->assertInstanceOf(Shelf::class, $shelf);
            if (1 === $shelf->getId()) {
                $shelf1Locations[] = $location;
            } else {
                $shelf2Locations[] = $location;
            }
        }

        // 检查Shelf1内的位置是否按ID升序排列
        if (count($shelf1Locations) > 1) {
            $this->assertLessThan(
                $shelf1Locations[1]->getId(),
                $shelf1Locations[0]->getId()
            );
        }
    }

    public function testOptimizePathDynamicStrategy(): void
    {
        $locations = [
            $this->createMockLocation(1, 1, 1),
            $this->createMockLocation(2, 2, 1),
        ];

        $result = $this->service->optimizePath($locations, 'dynamic');

        /** @var Location[] $optimizedSequence */
        $optimizedSequence = $result['optimized_sequence'];
        $this->assertCount(2, $optimizedSequence);
        $this->assertGreaterThanOrEqual(0, $result['efficiency_improvement']);
    }

    public function testOptimizePathWithConstraints(): void
    {
        $locations = [
            $this->createMockLocation(1, 1, 1),
            $this->createMockLocation(2, 2, 1),
        ];

        $constraints = [
            'max_distance' => 100.0,
            'avoid_zones' => [3],
        ];

        $result = $this->service->optimizePath($locations, 'shortest', $constraints);

        $this->assertIsArray($result);
        /** @var Location[] $optimizedSequence */
        $optimizedSequence = $result['optimized_sequence'];
        $this->assertCount(2, $optimizedSequence);
    }

    public function testOptimizeBatchPathsWithEmptyTasks(): void
    {
        $result = $this->service->optimizeBatchPaths([]);

        $this->assertIsArray($result);
        $this->assertSame([], $result['batch_results']);
        $this->assertSame(0.0, $result['total_distance_saved']);
        $this->assertSame(0.0, $result['total_time_saved']);
        $this->assertSame(0.0, $result['average_improvement']);
    }

    public function testOptimizeBatchPathsWithArrayTasks(): void
    {
        $tasks = [
            [
                'locations' => [
                    $this->createMockLocation(1, 1, 1),
                    $this->createMockLocation(2, 2, 1),
                ],
            ],
            [
                'locations' => [
                    $this->createMockLocation(3, 3, 2),
                    $this->createMockLocation(4, 4, 2),
                ],
            ],
        ];

        $result = $this->service->optimizeBatchPaths($tasks);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('batch_results', $result);
        $this->assertArrayHasKey('total_distance_saved', $result);
        $this->assertArrayHasKey('total_time_saved', $result);
        $this->assertArrayHasKey('average_improvement', $result);

        /** @var array<mixed> $batchResults */
        $batchResults = $result['batch_results'];
        $this->assertCount(2, $batchResults);
        $this->assertIsFloat($result['total_distance_saved']);
        $this->assertIsFloat($result['total_time_saved']);
        $this->assertIsFloat($result['average_improvement']);
    }

    public function testOptimizeBatchPathsWithBatchOptions(): void
    {
        $tasks = [
            [
                'locations' => [
                    $this->createMockLocation(1, 1, 1),
                    $this->createMockLocation(2, 2, 1),
                ],
            ],
        ];

        $batchOptions = [
            'strategy' => 's_shape',
            'constraints' => ['max_distance' => 50.0],
        ];

        $result = $this->service->optimizeBatchPaths($tasks, $batchOptions);

        $this->assertIsArray($result);
        /** @var array<mixed> $batchResults */
        $batchResults = $result['batch_results'];
        $this->assertCount(1, $batchResults);

        // 验证batch结果结构
        $batchResults = $result['batch_results'];
        $this->assertIsArray($batchResults);
        $batchResult = $batchResults[0];
        $this->assertIsArray($batchResult);
        $this->assertArrayHasKey('task_index', $batchResult);
        $this->assertArrayHasKey('result', $batchResult);
        $this->assertArrayHasKey('distance_saved', $batchResult);
        $this->assertArrayHasKey('time_saved', $batchResult);

        $this->assertSame(0, $batchResult['task_index']);
        $this->assertIsArray($batchResult['result']);
        $this->assertIsFloat($batchResult['distance_saved']);
        $this->assertIsFloat($batchResult['time_saved']);
    }

    public function testOptimizeBatchPathsSkipsEmptyTasks(): void
    {
        $tasks = [
            [], // 空任务
            ['locations' => []], // 空位置
            [
                'locations' => [
                    $this->createMockLocation(1, 1, 1),
                ],
            ],
        ];

        $result = $this->service->optimizeBatchPaths($tasks);

        // 只有最后一个任务有效
        /** @var array<mixed> $batchResults */
        $batchResults = $result['batch_results'];
        $this->assertCount(1, $batchResults);
        $batchResults = $result['batch_results'];
        $this->assertIsArray($batchResults);
        $firstResult = $batchResults[0];
        $this->assertIsArray($firstResult);
        $this->assertSame(2, $firstResult['task_index']);
    }

    public function testDistanceCalculationBetweenSameLocations(): void
    {
        $location = $this->createMockLocation(1, 1, 1);
        $locations = [$location, $location];

        $result = $this->service->optimizePath($locations);

        $this->assertSame(0.0, $result['total_distance']);
        $this->assertSame(0.0, $result['estimated_time']);
    }

    public function testDistanceCalculationBetweenDifferentZones(): void
    {
        $locations = [
            $this->createMockLocation(1, 1, 1), // Zone1
            $this->createMockLocation(2, 2, 2), // Zone2
        ];

        $result = $this->service->optimizePath($locations);

        // 不同Zone之间距离应该是10.0
        $this->assertSame(10.0, $result['total_distance']);
        $this->assertSame(6.67, $result['estimated_time']); // 10.0 / 1.5 = 6.67
    }

    public function testDistanceCalculationSameShelfDifferentLocations(): void
    {
        $locations = [
            $this->createMockLocation(1, 1, 1),
            $this->createMockLocation(2, 1, 1), // 同一Shelf
        ];

        $result = $this->service->optimizePath($locations);

        // 同一Shelf不同Location距离应该是1.0
        $this->assertSame(1.0, $result['total_distance']);
        $this->assertSame(0.67, $result['estimated_time']); // 1.0 / 1.5 = 0.67
    }

    public function testDistanceCalculationSameZoneDifferentShelves(): void
    {
        $locations = [
            $this->createMockLocation(1, 1, 1),
            $this->createMockLocation(2, 2, 1), // 同一Zone，不同Shelf
        ];

        $result = $this->service->optimizePath($locations);

        // 同一Zone不同Shelf距离应该是3.0
        $this->assertSame(3.0, $result['total_distance']);
        $this->assertSame(2.0, $result['estimated_time']); // 3.0 / 1.5 = 2.0
    }

    public function testEfficiencyImprovement(): void
    {
        // 创建一个距离较远的路径，优化后应该有改善
        $locations = [
            $this->createMockLocation(1, 1, 1), // Zone1, Shelf1
            $this->createMockLocation(2, 1, 2), // Zone2, Shelf1
            $this->createMockLocation(3, 2, 1), // Zone1, Shelf2
        ];

        $result = $this->service->optimizePath($locations, 'shortest');

        // 由于算法会尝试优化路径，efficiency_improvement应该>=0
        $this->assertGreaterThanOrEqual(0.0, $result['efficiency_improvement']);
    }

    /**
     * 创建模拟位置对象
     */
    private function createMockLocation(int $locationId, int $shelfId, int $zoneId): Location
    {
        $warehouse = new Warehouse();

        $zone = new Zone();
        $zone->setWarehouse($warehouse);
        $zone->setTitle("Zone {$zoneId}");
        $zone->setType('storage');

        // 使用反射设置私有ID属性
        $zoneReflection = new \ReflectionClass($zone);
        $idProperty = $zoneReflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($zone, $zoneId);

        $shelf = new Shelf();
        $shelf->setZone($zone);
        $shelf->setTitle("Shelf {$shelfId}");

        $shelfReflection = new \ReflectionClass($shelf);
        $shelfIdProperty = $shelfReflection->getProperty('id');
        $shelfIdProperty->setAccessible(true);
        $shelfIdProperty->setValue($shelf, $shelfId);

        $location = new Location();
        $location->setShelf($shelf);
        $location->setTitle("Location {$locationId}");

        $locationReflection = new \ReflectionClass($location);
        $locationIdProperty = $locationReflection->getProperty('id');
        $locationIdProperty->setAccessible(true);
        $locationIdProperty->setValue($location, $locationId);

        return $location;
    }
}
