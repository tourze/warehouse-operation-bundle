# warehouse-operation-bundle

[![PHP Version Require](https://img.shields.io/packagist/php-v/tourze/warehouse-operation-bundle)](
https://packagist.org/packages/tourze/warehouse-operation-bundle)
[![License](https://img.shields.io/packagist/l/tourze/warehouse-operation-bundle)](LICENSE)
[![Latest Version](https://img.shields.io/packagist/v/tourze/warehouse-operation-bundle)](
https://packagist.org/packages/tourze/warehouse-operation-bundle)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/warehouse-operation-bundle.svg?style=flat-square)](
https://codecov.io/gh/tourze/warehouse-operation-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/warehouse-operation-bundle)](
https://packagist.org/packages/tourze/warehouse-operation-bundle)

[English](README.md) | [中文](README.zh-CN.md)

一个用于仓库管理的Symfony组件，提供仓库、库区、货架和货位管理的实体和仓储类。

## 功能特性

- **仓库管理**：完整的仓库实体，包含联系人信息
- **库区管理**：仓库内的库区管理，支持面积和类型分类
- **货架管理**：库区内的货架管理，便于有序存储
- **货位管理**：货架内的具体存储位置管理
- **Doctrine集成**：完整的ORM支持和仓储类
- **时间戳跟踪**：自动记录创建和更新时间
- **用户跟踪**：自动记录创建和更新的用户

## 安装

```bash
composer require tourze/warehouse-operation-bundle
```

## 快速开始

1. 将组件添加到 `config/bundles.php`：

```php
<?php
return [
    // ... 其他组件
    WarehouseBundle\WarehouseBundle::class => ['all' => true],
];
```

2. 更新数据库架构：

```bash
php bin/console doctrine:schema:update --force
```

## 配置

本组件开箱即用，只需要最少的配置。但是您可以通过配置依赖项来自定义行为：

### 必需依赖

确保您已配置以下组件：

- `tourze/doctrine-timestamp-bundle` - 用于自动时间戳跟踪
- `tourze/doctrine-user-bundle` - 用于自动用户归属

### 可选配置

您可以通过扩展实体或创建自定义仓储类来配置自定义表名或其他ORM设置。

3. 开始使用实体：

```php
use Tourze\WarehouseOperationBundle\Entity\Warehouse;
use Tourze\WarehouseOperationBundle\Entity\Zone;
use Tourze\WarehouseOperationBundle\Entity\Shelf;
use Tourze\WarehouseOperationBundle\Entity\Location;

// 创建仓库
$warehouse = new Warehouse();
$warehouse->setCode('WH001');
$warehouse->setName('主仓库');
$warehouse->setContactName('张三');
$warehouse->setContactTel('1234567890');

// 创建库区
$zone = new Zone();
$zone->setTitle('A区');
$zone->setType('普通存储');
$zone->setWarehouse($warehouse);

// 创建货架
$shelf = new Shelf();
$shelf->setTitle('货架A1');
$shelf->setZone($zone);

// 创建货位
$location = new Location();
$location->setTitle('货位A1-01');
$location->setShelf($shelf);
```

## 使用方法

### 实体

本组件提供四个主要实体：

- **Warehouse（仓库）**：表示物理仓库，包含代码、名称和联系人详情
- **Zone（库区）**：表示仓库内的库区，包含标题、类型和面积
- **Shelf（货架）**：表示库区内的货架，用于有序存储
- **Location（货位）**：表示货架内的具体存储位置

### 仓储类

每个实体都有对应的仓储类：

- `WarehouseRepository`
- `ZoneRepository`
- `ShelfRepository`
- `LocationRepository`

### 高级用法

```php
// 使用仓储类
$warehouseRepo = $entityManager->getRepository(Warehouse::class);
$zoneRepo = $entityManager->getRepository(Zone::class);

// 根据代码查找仓库
$warehouse = $warehouseRepo->findOneBy(['code' => 'WH001']);

// 获取仓库中的所有库区
$zones = $warehouse->getZones();

// 获取库区中的所有货架
$zone = $zoneRepo->find(1);
$shelves = $zone->getShelves();
```

## 数据库架构

本组件创建以下数据表：

- `ims_wms_warehouse` - 仓库信息
- `ims_wms_zone` - 库区信息
- `ims_wms_shelf` - 货架信息
- `ims_wms_location` - 货位信息

## 依赖

- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM 3.0+
- tourze/doctrine-timestamp-bundle
- tourze/doctrine-user-bundle

## 许可证

本组件基于MIT许可证发布。详情请参阅[LICENSE](LICENSE)文件。
