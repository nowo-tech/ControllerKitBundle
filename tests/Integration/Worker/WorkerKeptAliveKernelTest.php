<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Integration\Worker;

use Doctrine\ORM\EntityManagerInterface;
use Nowo\ControllerKitBundle\Tests\Fixtures\OrmFactory;
use Nowo\ControllerKitBundle\Tests\Integration\ValueResolver\ScopedEntityTestKernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

use function dirname;
use function gc_collect_cycles;
use function memory_get_usage;
use function sprintf;

/**
 * REQ-RUNTIME-002: ONE kernel, `services_resetter` / `kernel.reset` never called (FrankenPHP worker, kernel reset
 * false). N iterations alternate two owners through `http_kernel` handle() + terminate(): `#[MapScopedEntity]` must
 * resolve each request to its own owner's entity, an entity of the other owner must stay a 404, and memory must stay
 * flat.
 */
final class WorkerKeptAliveKernelTest extends TestCase
{
    private const ITERATIONS = 1200;
    private const WARM_UP    = 100;
    private const MARGIN     = 4 * 1024 * 1024;

    public function testAlternatingOwnersInOneKernelResolveOnlyTheirOwnEntitiesAndKeepMemoryFlat(): void
    {
        (new Filesystem())->remove(dirname((new ScopedEntityTestKernel('test', false))->getCacheDir()));

        $kernel = new ScopedEntityTestKernel('test', false);
        $kernel->boot();

        try {
            $container = $kernel->getContainer();
            $manager   = $container->get('doctrine.orm.entity_manager');
            self::assertInstanceOf(EntityManagerInterface::class, $manager);
            OrmFactory::createSchema($manager);
            OrmFactory::seed($manager);

            $httpKernel = $container->get('http_kernel');
            self::assertInstanceOf(HttpKernelInterface::class, $httpKernel);
            self::assertInstanceOf(TerminableInterface::class, $httpKernel);

            $baseline = 0;
            for ($i = 0; $i < self::ITERATIONS; ++$i) {
                if ($i === self::WARM_UP) {
                    gc_collect_cycles();
                    $baseline = memory_get_usage();
                }

                [$owner, $mine, $other] = $i % 2 === 0 ? ['acme', 'ACME-1', 'GLOBEX-1'] : ['globex', 'GLOBEX-1', 'ACME-1'];

                $request  = Request::create(sprintf('/o/%s/items/%s', $owner, $mine));
                $response = $httpKernel->handle($request);
                self::assertSame(200, $response->getStatusCode(), sprintf('iteration %d', $i));
                self::assertSame($mine, $response->getContent(), sprintf('iteration %d', $i));
                $httpKernel->terminate($request, $response);

                $request  = Request::create(sprintf('/o/%s/items/%s', $owner, $other));
                $response = $httpKernel->handle($request);
                self::assertSame(404, $response->getStatusCode(), sprintf('iteration %d: cross-owner entity must stay a 404', $i));
                $httpKernel->terminate($request, $response);
            }

            gc_collect_cycles();
            $growth = memory_get_usage() - $baseline;
            self::assertLessThan(self::MARGIN, $growth, sprintf('memory grew by %d bytes over %d iterations', $growth, self::ITERATIONS - self::WARM_UP));
        } finally {
            $kernel->shutdown();
        }
    }
}
