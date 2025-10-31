<?php

namespace Tourze\WarehouseOperationBundle\Tests\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Model\PickingResult;

/**
 * @internal
 */
#[CoversClass(PickingResult::class)]
class PickingResultTest extends TestCase
{
    public function testClassExists(): void
    {
        $pickingResult = new PickingResult(['TEST001'], ['LOC001']);
        $this->assertInstanceOf(PickingResult::class, $pickingResult);
    }

    public function testCanInstantiate(): void
    {
        $pickingResult = new PickingResult(['TEST001'], ['LOC001']);
        $this->assertInstanceOf(PickingResult::class, $pickingResult);
    }

    public function testGetItems(): void
    {
        $items = ['TEST001', 'TEST002'];
        $locations = ['LOC001', 'LOC002'];
        $pickingResult = new PickingResult($items, $locations);

        $this->assertEquals($items, $pickingResult->getItems());
    }

    public function testGetLocations(): void
    {
        $items = ['TEST001', 'TEST002'];
        $locations = ['LOC001', 'LOC002'];
        $pickingResult = new PickingResult($items, $locations);

        $this->assertEquals($locations, $pickingResult->getLocations());
    }

    public function testGetInstructions(): void
    {
        $items = ['TEST001'];
        $locations = ['LOC001'];
        $instructions = ['Please pick TEST001 from LOC001'];
        $pickingResult = new PickingResult($items, $locations, $instructions);

        $this->assertEquals($instructions, $pickingResult->getInstructions());
    }

    public function testDefaultInstructions(): void
    {
        $items = ['TEST001'];
        $locations = ['LOC001'];
        $pickingResult = new PickingResult($items, $locations);

        $this->assertEquals([], $pickingResult->getInstructions());
    }
}
