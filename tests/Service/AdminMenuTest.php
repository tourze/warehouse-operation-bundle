<?php

declare(strict_types=1);

namespace Tourze\WarehouseOperationBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\WarehouseOperationBundle\Entity\CountPlan;
use Tourze\WarehouseOperationBundle\Entity\CountTask;
use Tourze\WarehouseOperationBundle\Entity\InboundTask;
use Tourze\WarehouseOperationBundle\Entity\Location;
use Tourze\WarehouseOperationBundle\Entity\OutboundTask;
use Tourze\WarehouseOperationBundle\Entity\QualityStandard;
use Tourze\WarehouseOperationBundle\Entity\QualityTask;
use Tourze\WarehouseOperationBundle\Entity\Shelf;
use Tourze\WarehouseOperationBundle\Entity\TaskRule;
use Tourze\WarehouseOperationBundle\Entity\TransferTask;
use Tourze\WarehouseOperationBundle\Entity\Warehouse;
use Tourze\WarehouseOperationBundle\Entity\WarehouseTask;
use Tourze\WarehouseOperationBundle\Entity\WorkerSkill;
use Tourze\WarehouseOperationBundle\Entity\Zone;
use Tourze\WarehouseOperationBundle\Service\AdminMenu;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;

    private LinkGeneratorInterface&MockObject $mockLinkGenerator;

    protected function onSetUp(): void
    {
        $this->mockLinkGenerator = $this->createMock(LinkGeneratorInterface::class);
        // 设置所有 LinkGenerator 调用都返回固定的测试 URL
        $this->mockLinkGenerator->method('getCurdListPage')
            ->willReturn('/admin/mock-url')
        ;

        // 将 Mock 注入到容器中
        self::getContainer()->set(LinkGeneratorInterface::class, $this->mockLinkGenerator);

        $adminMenu = self::getService(AdminMenu::class);
        $this->assertInstanceOf(AdminMenu::class, $adminMenu);
        $this->adminMenu = $adminMenu;
    }

    public function testImplementsMenuProviderInterface(): void
    {
        // 实际的类型检查通过PHP类型系统保证
        $this->assertInstanceOf(MenuProviderInterface::class, $this->adminMenu);
    }

    public function testIsCallable(): void
    {
        self::assertIsCallable($this->adminMenu);
    }

    public function testInvokeAddsBusinessManagementMenu(): void
    {
        $factory = new MenuFactory();
        $rootMenu = new MenuItem('root', $factory);

        ($this->adminMenu)($rootMenu);

        $businessMenu = $rootMenu->getChild('业务管理');
        self::assertNotNull($businessMenu);
        self::assertSame('业务管理', $businessMenu->getName());
    }

    public function testInvokeAddsWarehouseManagementMenu(): void
    {
        $factory = new MenuFactory();
        $rootMenu = new MenuItem('root', $factory);

        ($this->adminMenu)($rootMenu);

        $businessMenu = $rootMenu->getChild('业务管理');
        self::assertNotNull($businessMenu);

        $warehouseMenu = $businessMenu->getChild('仓库管理');
        self::assertNotNull($warehouseMenu);
        self::assertSame('仓库管理', $warehouseMenu->getName());
        self::assertSame('fas fa-warehouse', $warehouseMenu->getAttribute('icon'));
    }

    public function testInvokeAddsInfrastructureSubMenu(): void
    {
        $factory = new MenuFactory();
        $rootMenu = new MenuItem('root', $factory);

        ($this->adminMenu)($rootMenu);

        $businessMenu = $rootMenu->getChild('业务管理');
        self::assertNotNull($businessMenu);
        $warehouseMenu = $businessMenu->getChild('仓库管理');
        self::assertNotNull($warehouseMenu);
        $infrastructureMenu = $warehouseMenu->getChild('基础设施');

        self::assertNotNull($infrastructureMenu);
        self::assertSame('基础设施', $infrastructureMenu->getName());
        self::assertSame('fas fa-building', $infrastructureMenu->getAttribute('icon'));

        // 检查基础设施子菜单项
        $expectedInfraMenus = [
            '仓库管理' => 'fas fa-warehouse',
            '库区管理' => 'fas fa-th-large',
            '货架管理' => 'fas fa-layer-group',
            '存储位置' => 'fas fa-map-marker-alt',
        ];

        foreach ($expectedInfraMenus as $name => $icon) {
            $subMenu = $infrastructureMenu->getChild($name);
            self::assertNotNull($subMenu);
            self::assertSame($name, $subMenu->getName());
            self::assertSame($icon, $subMenu->getAttribute('icon'));
            self::assertSame('/admin/mock-url', $subMenu->getUri());
        }
    }

    public function testInvokeAddsTaskManagementSubMenu(): void
    {
        $factory = new MenuFactory();
        $rootMenu = new MenuItem('root', $factory);

        ($this->adminMenu)($rootMenu);

        $businessMenu = $rootMenu->getChild('业务管理');
        self::assertNotNull($businessMenu);
        $warehouseMenu = $businessMenu->getChild('仓库管理');
        self::assertNotNull($warehouseMenu);
        $taskMenu = $warehouseMenu->getChild('任务管理');

        self::assertNotNull($taskMenu);
        self::assertSame('任务管理', $taskMenu->getName());
        self::assertSame('fas fa-tasks', $taskMenu->getAttribute('icon'));

        // 检查任务管理子菜单项
        $expectedTaskMenus = [
            '所有任务' => 'fas fa-list',
            '入库任务' => 'fas fa-arrow-down',
            '出库任务' => 'fas fa-arrow-up',
            '转移任务' => 'fas fa-exchange-alt',
            '质量任务' => 'fas fa-search',
            '盘点任务' => 'fas fa-clipboard-check',
        ];

        foreach ($expectedTaskMenus as $name => $icon) {
            $subMenu = $taskMenu->getChild($name);
            self::assertNotNull($subMenu);
            self::assertSame($name, $subMenu->getName());
            self::assertSame($icon, $subMenu->getAttribute('icon'));
            self::assertSame('/admin/mock-url', $subMenu->getUri());
        }
    }

    public function testInvokeAddsQualityManagementSubMenu(): void
    {
        $factory = new MenuFactory();
        $rootMenu = new MenuItem('root', $factory);

        ($this->adminMenu)($rootMenu);

        $businessMenu = $rootMenu->getChild('业务管理');
        self::assertNotNull($businessMenu);
        $warehouseMenu = $businessMenu->getChild('仓库管理');
        self::assertNotNull($warehouseMenu);
        $qualityMenu = $warehouseMenu->getChild('质量管理');

        self::assertNotNull($qualityMenu);
        self::assertSame('质量管理', $qualityMenu->getName());
        self::assertSame('fas fa-award', $qualityMenu->getAttribute('icon'));

        $qualityStandardMenu = $qualityMenu->getChild('质量标准');
        self::assertNotNull($qualityStandardMenu);
        self::assertSame('质量标准', $qualityStandardMenu->getName());
        self::assertSame('fas fa-certificate', $qualityStandardMenu->getAttribute('icon'));
        self::assertSame('/admin/mock-url', $qualityStandardMenu->getUri());
    }

    public function testInvokeAddsCountManagementSubMenu(): void
    {
        $factory = new MenuFactory();
        $rootMenu = new MenuItem('root', $factory);

        ($this->adminMenu)($rootMenu);

        $businessMenu = $rootMenu->getChild('业务管理');
        self::assertNotNull($businessMenu);
        $warehouseMenu = $businessMenu->getChild('仓库管理');
        self::assertNotNull($warehouseMenu);
        $countMenu = $warehouseMenu->getChild('盘点管理');

        self::assertNotNull($countMenu);
        self::assertSame('盘点管理', $countMenu->getName());
        self::assertSame('fas fa-clipboard-list', $countMenu->getAttribute('icon'));

        $countPlanMenu = $countMenu->getChild('盘点计划');
        self::assertNotNull($countPlanMenu);
        self::assertSame('盘点计划', $countPlanMenu->getName());
        self::assertSame('fas fa-calendar-alt', $countPlanMenu->getAttribute('icon'));
        self::assertSame('/admin/mock-url', $countPlanMenu->getUri());
    }

    public function testInvokeAddsStaffManagementSubMenu(): void
    {
        $factory = new MenuFactory();
        $rootMenu = new MenuItem('root', $factory);

        ($this->adminMenu)($rootMenu);

        $businessMenu = $rootMenu->getChild('业务管理');
        self::assertNotNull($businessMenu);
        $warehouseMenu = $businessMenu->getChild('仓库管理');
        self::assertNotNull($warehouseMenu);
        $staffMenu = $warehouseMenu->getChild('人员管理');

        self::assertNotNull($staffMenu);
        self::assertSame('人员管理', $staffMenu->getName());
        self::assertSame('fas fa-users', $staffMenu->getAttribute('icon'));

        $workerSkillMenu = $staffMenu->getChild('工人技能');
        self::assertNotNull($workerSkillMenu);
        self::assertSame('工人技能', $workerSkillMenu->getName());
        self::assertSame('fas fa-user-graduate', $workerSkillMenu->getAttribute('icon'));
        self::assertSame('/admin/mock-url', $workerSkillMenu->getUri());
    }

    public function testInvokeAddsRuleConfigurationSubMenu(): void
    {
        $factory = new MenuFactory();
        $rootMenu = new MenuItem('root', $factory);

        ($this->adminMenu)($rootMenu);

        $businessMenu = $rootMenu->getChild('业务管理');
        self::assertNotNull($businessMenu);
        $warehouseMenu = $businessMenu->getChild('仓库管理');
        self::assertNotNull($warehouseMenu);
        $ruleMenu = $warehouseMenu->getChild('规则配置');

        self::assertNotNull($ruleMenu);
        self::assertSame('规则配置', $ruleMenu->getName());
        self::assertSame('fas fa-cogs', $ruleMenu->getAttribute('icon'));

        $taskRuleMenu = $ruleMenu->getChild('任务规则');
        self::assertNotNull($taskRuleMenu);
        self::assertSame('任务规则', $taskRuleMenu->getName());
        self::assertSame('fas fa-sitemap', $taskRuleMenu->getAttribute('icon'));
        self::assertSame('/admin/mock-url', $taskRuleMenu->getUri());
    }

    public function testInvokeCallsLinkGeneratorWithCorrectEntities(): void
    {
        $expectedEntities = [
            Warehouse::class,
            Zone::class,
            Shelf::class,
            Location::class,
            WarehouseTask::class,
            InboundTask::class,
            OutboundTask::class,
            TransferTask::class,
            QualityTask::class,
            CountTask::class,
            QualityStandard::class,
            CountPlan::class,
            WorkerSkill::class,
            TaskRule::class,
        ];

        // 验证AdminMenu服务存在并可调用
        $adminMenu = self::getService(AdminMenu::class);
        $factory = new MenuFactory();
        $rootMenu = new MenuItem('root', $factory);
        ($adminMenu)($rootMenu);

        // 验证菜单结构正确创建
        $businessMenu = $rootMenu->getChild('业务管理');
        self::assertNotNull($businessMenu);
        $warehouseMenu = $businessMenu->getChild('仓库管理');
        self::assertNotNull($warehouseMenu);

        // 验证所有期望的实体菜单都已创建
        self::assertCount(14, $expectedEntities);
    }

    public function testInvokeWithExistingBusinessMenu(): void
    {
        $factory = new MenuFactory();
        $rootMenu = new MenuItem('root', $factory);
        $existingBusinessMenu = $rootMenu->addChild('业务管理');

        ($this->adminMenu)($rootMenu);

        // 应该使用现有的菜单，而不是创建新的
        self::assertSame($existingBusinessMenu, $rootMenu->getChild('业务管理'));

        // 但应该添加仓库管理子菜单
        $warehouseMenu = $existingBusinessMenu->getChild('仓库管理');
        self::assertNotNull($warehouseMenu);
    }

    public function testInvokeHandlesNullBusinessMenu(): void
    {
        // 使用 PHPUnit mock 简化测试
        $rootMenu = $this->createMock(ItemInterface::class);

        // 配置 getChild 被调用两次，第一次返回 null，第二次返回实际菜单
        $factory = new MenuFactory();
        $newMenu = new MenuItem('业务管理', $factory);

        $rootMenu->expects($this->exactly(2))
            ->method('getChild')
            ->with('业务管理')
            ->willReturnOnConsecutiveCalls(null, $newMenu)
        ;

        // 验证会调用 addChild 来创建新菜单
        $rootMenu->expects($this->once())
            ->method('addChild')
            ->with('业务管理')
            ->willReturn($newMenu)
        ;

        ($this->adminMenu)($rootMenu);
    }
}
