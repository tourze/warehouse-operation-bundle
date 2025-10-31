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

A Symfony bundle for warehouse management, providing entities and repositories for
warehouse, zone, shelf, and location management.

## Features

- **Warehouse Management**: Complete warehouse entity with contact information
- **Zone Management**: Warehouse zones with area and type classification
- **Shelf Management**: Shelves within zones for organized storage
- **Location Management**: Specific storage locations within shelves
- **Doctrine Integration**: Full ORM support with repositories
- **Timestamp Tracking**: Automatic creation and update timestamps
- **User Tracking**: Automatic user attribution for created/updated records

## Installation

```bash
composer require tourze/warehouse-operation-bundle
```

## Quick Start

1. Add the bundle to your `config/bundles.php`:

```php
<?php
return [
    // ... other bundles
    WarehouseBundle\WarehouseBundle::class => ['all' => true],
];
```

2. Update your database schema:

```bash
php bin/console doctrine:schema:update --force
```

## Configuration

The bundle works out of the box with minimal configuration. However, you can customize
the behavior by configuring the dependencies:

### Required Dependencies

Make sure you have the following bundles configured:

- `tourze/doctrine-timestamp-bundle` - for automatic timestamp tracking
- `tourze/doctrine-user-bundle` - for automatic user attribution

### Optional Configuration

You can configure custom table names or other ORM settings by extending the entities
or creating custom repositories.

3. Start using the entities:

```php
use Tourze\WarehouseOperationBundle\Entity\Warehouse;
use Tourze\WarehouseOperationBundle\Entity\Zone;
use Tourze\WarehouseOperationBundle\Entity\Shelf;
use Tourze\WarehouseOperationBundle\Entity\Location;

// Create a warehouse
$warehouse = new Warehouse();
$warehouse->setCode('WH001');
$warehouse->setName('Main Warehouse');
$warehouse->setContactName('John Doe');
$warehouse->setContactTel('1234567890');

// Create a zone
$zone = new Zone();
$zone->setTitle('Zone A');
$zone->setType('General Storage');
$zone->setWarehouse($warehouse);

// Create a shelf
$shelf = new Shelf();
$shelf->setTitle('Shelf A1');
$shelf->setZone($zone);

// Create a location
$location = new Location();
$location->setTitle('Location A1-01');
$location->setShelf($shelf);
```

## Usage

### Entities

The bundle provides four main entities:

- **Warehouse**: Represents a physical warehouse with code, name, and contact details
- **Zone**: Represents zones within a warehouse with title, type, and area
- **Shelf**: Represents shelves within zones for organized storage
- **Location**: Represents specific storage locations within shelves

### Repositories

Each entity has its corresponding repository:

- `WarehouseRepository`
- `ZoneRepository`
- `ShelfRepository`
- `LocationRepository`

### Advanced Usage

```php
// Using repositories
$warehouseRepo = $entityManager->getRepository(Warehouse::class);
$zoneRepo = $entityManager->getRepository(Zone::class);

// Find warehouse by code
$warehouse = $warehouseRepo->findOneBy(['code' => 'WH001']);

// Get all zones in a warehouse
$zones = $warehouse->getZones();

// Get all shelves in a zone
$zone = $zoneRepo->find(1);
$shelves = $zone->getShelves();
```

## Database Schema

The bundle creates the following tables:

- `ims_wms_warehouse` - Warehouse information
- `ims_wms_zone` - Zone information
- `ims_wms_shelf` - Shelf information
- `ims_wms_location` - Location information

## Dependencies

- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM 3.0+
- tourze/doctrine-timestamp-bundle
- tourze/doctrine-user-bundle

## License

This bundle is released under the MIT License. See the [LICENSE](LICENSE) file for details.
