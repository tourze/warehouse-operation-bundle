<?php

namespace Tourze\WarehouseOperationBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\WarehouseOperationBundle\Service\ConfigService;

/**
 * @internal
 */
#[CoversClass(ConfigService::class)]
#[RunTestsInSeparateProcesses]
class ConfigServiceTest extends AbstractIntegrationTestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $originalEnv = [];

    protected function onSetUp(): void
    {
        // 保存原始环境变量
        $this->originalEnv = [
            'WAREHOUSE_TASK_TIMEOUT' => $_ENV['WAREHOUSE_TASK_TIMEOUT'] ?? null,
            'WAREHOUSE_AUTO_ASSIGN' => $_ENV['WAREHOUSE_AUTO_ASSIGN'] ?? null,
            'WAREHOUSE_QUALITY_REQUIRED' => $_ENV['WAREHOUSE_QUALITY_REQUIRED'] ?? null,
            'WAREHOUSE_MAX_CONCURRENT_TASKS' => $_ENV['WAREHOUSE_MAX_CONCURRENT_TASKS'] ?? null,
        ];
    }

    protected function onTearDown(): void
    {
        // 恢复原始环境变量
        foreach ($this->originalEnv as $key => $value) {
            if (null === $value) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $value;
            }
        }
    }

    public function testGetTaskTimeoutDefaultValue(): void
    {
        unset($_ENV['WAREHOUSE_TASK_TIMEOUT']);

        $config = parent::getService(ConfigService::class);
        $this->assertEquals(3600, $config->getTaskTimeout());
    }

    public function testGetTaskTimeoutFromEnv(): void
    {
        $_ENV['WAREHOUSE_TASK_TIMEOUT'] = '7200';

        $config = parent::getService(ConfigService::class);
        $this->assertEquals(7200, $config->getTaskTimeout());
    }

    public function testIsAutoAssignDefaultValue(): void
    {
        unset($_ENV['WAREHOUSE_AUTO_ASSIGN']);

        $config = parent::getService(ConfigService::class);
        $this->assertTrue($config->isAutoAssignEnabled());
    }

    public function testIsAutoAssignFromEnv(): void
    {
        $_ENV['WAREHOUSE_AUTO_ASSIGN'] = 'false';

        $config = parent::getService(ConfigService::class);
        $this->assertFalse($config->isAutoAssignEnabled());
    }

    public function testIsQualityRequiredDefaultValue(): void
    {
        unset($_ENV['WAREHOUSE_QUALITY_REQUIRED']);

        $config = parent::getService(ConfigService::class);
        $this->assertTrue($config->isQualityCheckRequired());
    }

    public function testIsQualityCheckRequiredFromEnv(): void
    {
        $_ENV['WAREHOUSE_QUALITY_REQUIRED'] = 'true';

        $config = parent::getService(ConfigService::class);
        $this->assertTrue($config->isQualityCheckRequired());
    }

    public function testGetMaxConcurrentTasksDefaultValue(): void
    {
        unset($_ENV['WAREHOUSE_MAX_CONCURRENT_TASKS']);

        $config = parent::getService(ConfigService::class);
        $this->assertEquals(100, $config->getMaxConcurrentTasks());
    }

    public function testGetMaxConcurrentTasksFromEnv(): void
    {
        $_ENV['WAREHOUSE_MAX_CONCURRENT_TASKS'] = '200';

        $config = parent::getService(ConfigService::class);
        $this->assertEquals(200, $config->getMaxConcurrentTasks());
    }

    public function testBooleanEnvironmentVariableHandling(): void
    {
        $config = parent::getService(ConfigService::class);

        // 测试各种布尔值表示
        $_ENV['WAREHOUSE_AUTO_ASSIGN'] = '1';
        $this->assertTrue($config->isAutoAssignEnabled());

        $_ENV['WAREHOUSE_AUTO_ASSIGN'] = '0';
        $this->assertFalse($config->isAutoAssignEnabled());

        $_ENV['WAREHOUSE_AUTO_ASSIGN'] = 'yes';
        $this->assertTrue($config->isAutoAssignEnabled());

        $_ENV['WAREHOUSE_AUTO_ASSIGN'] = 'no';
        $this->assertFalse($config->isAutoAssignEnabled());
    }
}
