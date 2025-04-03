<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Temporal\Samples\SimpleBatch;

use Carbon\CarbonInterval;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Promise;
use Temporal\Workflow;
use Throwable;

class SimpleBatchWorkflow implements SimpleBatchWorkflowInterface
{
    /**
     * @var array
     */
    private array $results = [];

    /**
     * @var array
     */
    private array $pending = [];

    /**
     * @var SimpleBatchActivityInterface
     */
    private $batchActivity;

    public function __construct()
    {
        $this->batchActivity = Workflow::newActivityStub(
            SimpleBatchActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::hours(6))
                ->withScheduleToStartTimeout(CarbonInterval::hours(4))
                ->withScheduleToCloseTimeout(CarbonInterval::hours(6))
                ->withRetryOptions(
                    RetryOptions::new()
                        ->withMaximumAttempts(100)
                        ->withInitialInterval(CarbonInterval::second(10))
                        ->withMaximumInterval(CarbonInterval::seconds(100))
                )
        );
    }

    /**
     * @inheritDoc
     */
    public function start(int $batchId, array $options)
    {
        // Notify the batch processing start.
        yield $this->batchActivity->batchProcessingStarted($batchId, $options);

        $itemIds = yield $this->batchActivity->getBatchItemIds($batchId, $options);

        $promises = [];
        foreach($itemIds as $itemId)
        {
            // Set the batch item as pending.
            $this->pending[$itemId] = true;
            // Process the batch item.
            $promises[$itemId] = Workflow::async(
                function() use($itemId, $batchId) {
                    // Notify the item processing start.
                    yield $this->batchActivity->itemProcessingStarted($itemId, $batchId);

                    // This activity randomly throws an exception.
                    $output = yield $this->batchActivity->processItem($itemId, $batchId);

                    // Notify the item processing end.
                    yield $this->batchActivity->itemProcessingEnded($itemId, $batchId);

                    return $output;
                }
            )
            ->then(
                fn($output) => $this->results[$itemId] = [
                    'success' => true,
                    'output' => $output,
                ],
                fn(Throwable $e) => $this->results[$itemId] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ]
            )
            // We are calling always() instead of finally() because the Temporal PHP SDK depends on
            // react/promise 2.9. Will need to change to finally() when upgrading to react/promise 3.x.
            ->always(fn() => $this->pending[$itemId] = false);
        }

        // Wait for all the async calls to terminate.
        yield Promise::all($promises);

        // Notify the batch processing end.
        yield $this->batchActivity->batchProcessingEnded($batchId, $this->results);

        return $this->results;
    }

    /**
     * @inheritDoc
     */
    public function getAvailableResults(): array
    {
        return $this->results;
    }

    /**
     * @inheritDoc
     */
    public function getPendingTasks(): array
    {
        return array_keys(array_filter($this->pending, fn($pending) => $pending));
    }
}
