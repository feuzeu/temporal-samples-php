<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Temporal\Samples\SimpleBatch;

use Exception;
use Psr\Log\LoggerInterface;
use Temporal\SampleUtils\Logger;

class SimpleBatchActivity implements SimpleBatchActivityInterface
{
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    /**
     * @inheritDoc
     */
    public function getBatchItemIds(int $batchId, int $minItemCount, int $maxItemCount): array
    {
        return array_map(fn(int $itemId) => ($batchId % 100) * 1000 + $itemId,
            range(101, random_int(100 + $minItemCount, 100 + $maxItemCount)));
    }

    /**
     * @inheritDoc
     */
    public function itemProcessingStarted(int $itemId, int $batchId): bool
    {
        $this->log("Started processing of item %d of batch %d.", $itemId, $batchId);
        return true;
    }

    /**
     * @inheritDoc
     */
    public function processItem(int $itemId, int $batchId): int
    {
        $this->log("Processing item %d of batch %d.", $itemId, $batchId);

        $random = random_int(0, 90);
        // Wait for max 1 second.
        usleep($random % 10000);

        // Randomly throw an error.
        if($random > 30)
        {
            throw new Exception(sprintf("Error while processing of item %d of batch %d.", $itemId, $batchId));
        }
        return $random;
    }

    /**
     * @inheritDoc
     */
    public function itemProcessingEnded(int $itemId, int $batchId): bool
    {
        $this->log("Ended processing of item %d of batch %d.", $itemId, $batchId);
        return true;
    }

    /**
     * @param string $message
     * @param mixed ...$arg
     */
    private function log(string $message, ...$arg)
    {
        $this->logger->debug(sprintf($message, ...$arg));
    }
}
