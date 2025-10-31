<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\WarehouseOperationBundle\Exception\StockIntegrationException;

/**
 * @internal
 */
#[CoversClass(StockIntegrationException::class)]
final class StockIntegrationExceptionTest extends AbstractExceptionTestCase
{
    protected function onSetUp(): void
    {
        // 可以在这里添加自定义的初始化逻辑
    }

    public function test异常可以正确创建和抛出(): void
    {
        $message = 'Stock integration operation failed';
        $code = 500;
        $previous = new \Exception('Previous exception');

        $exception = new StockIntegrationException($message, $code, $previous);

        $this->assertInstanceOf(StockIntegrationException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test异常可以被捕获(): void
    {
        $this->expectException(StockIntegrationException::class);
        $this->expectExceptionMessage('Stock integration failed');

        throw new StockIntegrationException('Stock integration failed');
    }

    public function test异常继承自RuntimeException(): void
    {
        $exception = new StockIntegrationException('Test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
    }

    public function test异常默认值(): void
    {
        $exception = new StockIntegrationException();

        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function test异常可以携带详细信息(): void
    {
        $detailMessage = 'Failed to sync stock data: Connection timeout';
        $errorCode = 1001;

        $exception = new StockIntegrationException($detailMessage, $errorCode);

        $this->assertEquals($detailMessage, $exception->getMessage());
        $this->assertEquals($errorCode, $exception->getCode());
    }
}
