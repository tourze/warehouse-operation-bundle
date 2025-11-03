<?php

namespace Tourze\WarehouseOperationBundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\StockManageBundle\StockManageBundle;
use Tourze\EasyAdminMenuBundle\EasyAdminMenuBundle;

class WarehouseOperationBundle extends Bundle implements BundleDependencyInterface
{
    public static function getBundleDependencies(): array
    {
        return [
            DoctrineBundle::class => ['all' => true],
            TwigBundle::class => ['all' => true],
            EasyAdminBundle::class => ['all' => true],
            StockManageBundle::class => ['all' => true],
            EasyAdminMenuBundle::class => ['all' => true],
        ];
    }
}
