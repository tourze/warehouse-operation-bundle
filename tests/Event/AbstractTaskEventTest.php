<?php

namespace Tourze\WarehouseOperationBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Event\AbstractTaskEvent;
use Tourze\WarehouseOperationBundle\Event\TaskCreatedEvent;

/**
 * @internal
 */
#[CoversClass(AbstractTaskEvent::class)]
class AbstractTaskEventTest extends TestCase
{
    public function testAbstractEventClassIsAbstract(): void
    {
        $reflection = new \ReflectionClass(AbstractTaskEvent::class);
        $this->assertTrue($reflection->isAbstract(), 'AbstractTaskEvent should be abstract');
        $this->assertFalse($reflection->isInstantiable(), 'Abstract class should not be instantiable');
    }

    public function testConcreteEventCanBeInstantiated(): void
    {
        $task = new InboundTask();
        $context = ['test' => 'data'];
        $event = new TaskCreatedEvent($task, 'test_user', 'system', [], $context);

        $this->assertInstanceOf(AbstractTaskEvent::class, $event);
        $this->assertSame($task, $event->getTask());
        $this->assertEquals($context, $event->getContext());
    }

    public function testDefaultContext(): void
    {
        $task = new InboundTask();
        $event = new TaskCreatedEvent($task, 'test_user');

        $this->assertEquals([], $event->getContext());
    }
}
