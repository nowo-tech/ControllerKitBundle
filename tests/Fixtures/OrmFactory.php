<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Fixtures;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\Middleware;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Item;
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Owner;
use Nowo\ControllerKitBundle\Tests\Fixtures\Type\StrictCodeType;
use Psr\Log\LoggerInterface;

use const PHP_VERSION_ID;

/**
 * In-memory SQLite entity manager with the Owner/Item fixture schema.
 */
final class OrmFactory
{
    public static function registerTypes(): void
    {
        if (!Type::hasType(StrictCodeType::NAME)) {
            Type::addType(StrictCodeType::NAME, StrictCodeType::class);
        }
    }

    public static function createEntityManager(?LoggerInterface $sqlLogger = null): EntityManagerInterface
    {
        self::registerTypes();

        $config = ORMSetup::createAttributeMetadataConfig([__DIR__ . '/Entity'], true);
        if (PHP_VERSION_ID >= 80400) {
            $config->enableNativeLazyObjects(true);
        }

        if ($sqlLogger instanceof LoggerInterface) {
            $config->setMiddlewares([new Middleware($sqlLogger)]);
        }

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $config);
        $manager    = new EntityManager($connection, $config);

        self::createSchema($manager);

        return $manager;
    }

    public static function createSchema(EntityManagerInterface $manager): void
    {
        $schemaTool = new SchemaTool($manager);
        $schemaTool->createSchema([
            $manager->getClassMetadata(Owner::class),
            $manager->getClassMetadata(Item::class),
        ]);
    }

    /**
     * Two owners (`acme`, `globex`) with one item each (`ACME-1`, `GLOBEX-1`).
     *
     * @return array{acme: Owner, globex: Owner, acmeItem: Item, globexItem: Item}
     */
    public static function seed(EntityManagerInterface $manager): array
    {
        $acme       = new Owner('acme');
        $globex     = new Owner('globex');
        $acmeItem   = new Item($acme, 'ACME-1');
        $globexItem = new Item($globex, 'GLOBEX-1');

        foreach ([$acme, $globex, $acmeItem, $globexItem] as $entity) {
            $manager->persist($entity);
        }
        $manager->flush();
        $manager->clear();

        return ['acme' => $acme, 'globex' => $globex, 'acmeItem' => $acmeItem, 'globexItem' => $globexItem];
    }
}
