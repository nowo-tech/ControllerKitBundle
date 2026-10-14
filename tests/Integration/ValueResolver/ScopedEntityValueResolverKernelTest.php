<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Integration\ValueResolver;

use Doctrine\ORM\EntityManagerInterface;
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Item;
use Nowo\ControllerKitBundle\Tests\Fixtures\Entity\Owner;
use Nowo\ControllerKitBundle\Tests\Fixtures\OrmFactory;
use Nowo\ControllerKitBundle\ValueResolver\ScopedEntityValueResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use function dirname;
use function sprintf;

/**
 * End to end: routing + argument resolution + DoctrineBundle on in-memory SQLite (spec 003 SC-002).
 */
final class ScopedEntityValueResolverKernelTest extends TestCase
{
    private ScopedEntityTestKernel $kernel;

    /** @var array{acme: Owner, globex: Owner, acmeItem: Item, globexItem: Item} */
    private array $rows;

    protected function setUp(): void
    {
        (new Filesystem())->remove(dirname((new ScopedEntityTestKernel('test', true))->getCacheDir()));

        $this->kernel = new ScopedEntityTestKernel('test', true);
        $this->kernel->boot();

        $manager = $this->kernel->getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);
        OrmFactory::createSchema($manager);
        $this->rows = OrmFactory::seed($manager);
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
    }

    public function testResolverIsRegisteredAsTargetedValueResolver(): void
    {
        $container = $this->kernel->getContainer();

        self::assertFalse($container->has(ScopedEntityValueResolver::class), 'private service');
        self::assertSame('ACME-1', $this->get(sprintf('/owners/%d/items/%d', $this->rows['acme']->getId(), $this->rows['acmeItem']->getId()))->getContent());
    }

    public function testResolvesByNonPrimaryFieldsThroughAHostShortcutAttribute(): void
    {
        self::assertSame('GLOBEX-1', $this->get('/o/globex/items/GLOBEX-1')->getContent());
    }

    public function testEntityOfAnotherOwnerIsTheSame404AsAMissingOne(): void
    {
        foreach ([
            sprintf('/owners/%d/items/%d', $this->rows['acme']->getId(), $this->rows['globexItem']->getId()),
            sprintf('/owners/%d/items/999', $this->rows['acme']->getId()),
            '/o/acme/items/GLOBEX-1',
            '/o/acme/items/ACME-404',
            '/o/acme/items/malformed',
            '/o/nobody/items/ACME-1',
        ] as $path) {
            try {
                $this->get($path);
                self::fail('Expected 404 for ' . $path);
            } catch (NotFoundHttpException $exception) {
                self::assertSame(sprintf('"%s" object not found by "%s".', Item::class, ScopedEntityValueResolver::class), $exception->getMessage(), $path);
            }
        }
    }

    public function testNullableArgumentReceivesNull(): void
    {
        self::assertSame('ACME-1', $this->get('/o/acme/optional/ACME-1')->getContent());
        self::assertSame('none', $this->get('/o/acme/optional/GLOBEX-1')->getContent());
    }

    private function get(string $path): Response
    {
        return $this->kernel->handle(Request::create($path), HttpKernelInterface::MAIN_REQUEST, false);
    }
}
