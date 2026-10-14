<?php

declare(strict_types=1);

namespace Nowo\ControllerKitBundle\Tests\Fixtures;

use Psr\Log\AbstractLogger;
use Stringable;

use function is_string;

/**
 * Collects the SQL logged by Doctrine's DBAL logging middleware.
 */
final class SqlLogger extends AbstractLogger
{
    /** @var list<string> */
    public array $sql = [];

    public function reset(): void
    {
        $this->sql = [];
    }

    /**
     * @param array<mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        if (isset($context['sql']) && is_string($context['sql'])) {
            $this->sql[] = $context['sql'];
        }
    }
}
