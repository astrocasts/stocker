<?php

namespace Astrocasts\Stocker\EventSourcing;

use Amp\Promise;
use Amp\Success;
use Prooph\EventStoreClient\EventStoreConnection;
use Prooph\EventStoreClient\ExpectedVersion;
use Prooph\EventStoreClient\Internal\Consts;
use Prooph\EventStoreClient\SliceReadStatus;
use Prooph\EventStoreClient\StreamEventsSlice;
use Prooph\EventStoreClient\UserCredentials;
use function Amp\call;
use Prooph\EventStoreClient\WriteResult;

class AggregateRepository
{
    /** @var EventStoreConnection */
    protected $eventStoreConnection;

    /** @var MessageTransformer */
    protected $transformer;

    /** @var string */
    protected $streamCategory;

    /** @var string */
    protected $aggregateRootClassName;

    /** @var bool */
    protected $optimisticConcurrency;

    public function __construct(
        EventStoreConnection $eventStoreConnection,
        MessageTransformer $transformer,
        string $streamCategory,
        string $aggregateRootClassName,
        bool $useOptimisticConcurrencyByDefault = true
    ) {
        $this->eventStoreConnection = $eventStoreConnection;
        $this->transformer = $transformer;
        $this->streamCategory = $streamCategory;
        $this->aggregateRootClassName = $aggregateRootClassName;
        $this->optimisticConcurrency = $useOptimisticConcurrencyByDefault;
    }

    public function saveAggregateRoot(
        AggregateRoot $aggregateRoot,
        int $expectedVersion = null,
        UserCredentials $credentials = null
    ): Promise
    {
        return call(function () use ($aggregateRoot, $expectedVersion, $credentials) {
            $domainEvents = $aggregateRoot->popRecordedEvents();

            if (empty($domainEvents)) {
                return new Success();
            }

            $aggregateId = $aggregateRoot->aggregateId();
            $stream = $this->streamCategory . '-' . $aggregateId;

            $eventData = [];

            foreach ($domainEvents as $event) {
                $eventData[] = $this->transformer->toEventData($event); // EventData
            }

            if (null === $expectedVersion) {
                $expectedVersion = $this->optimisticConcurrency
                    ? $aggregateRoot->expectedVersion()
                    : ExpectedVersion::ANY;
            }

            /** @var WriteResult $writeResult */
            $writeResult = yield $this->eventStoreConnection
                ->appendToStreamAsync(
                    $stream,
                    $expectedVersion,
                    $eventData, // this is an array
                    $credentials
                );

            $aggregateRoot->setExpectedVersion(
                $writeResult->nextExpectedVersion()
            );

            return $aggregateRoot;
        });
    }

    public function getAggregateRoot(
        string $aggregateId,
        UserCredentials $credentials = null
    ): Promise
    {
        return call(function () use ($aggregateId, $credentials) {
            $stream = $this->streamCategory . '-' . $aggregateId;

            $start = 0;
            $count = Consts::MAX_READ_SIZE;

            do {
                $events = [];

                /** @var StreamEventsSlice $streamEventsSlice */
                $streamEventsSlice = yield $this->eventStoreConnection
                    ->readStreamEventsForwardAsync(
                        $stream,
                        $start,
                        $count,
                        true,
                        $credentials
                    );

                if (! $streamEventsSlice->status()->equals(
                    SliceReadStatus::success())
                ) {
                    return null;
                }

                $start = $streamEventsSlice->nextEventNumber();

                foreach ($streamEventsSlice->events() as $event) {
                    $events[] = $this->transformer->toDomainEvent($event);
                }

                if (isset($aggregateRoot)) {
                    assert($aggregateRoot instanceof AggregateRoot);
                    $aggregateRoot->replay($events);
                } else {
                    $className = $this->aggregateRootClassName;
                    $aggregateRoot = $className::reconstituteFromHistory($events);
                }
            } while (! $streamEventsSlice->isEndOfStream());

            $aggregateRoot->setExpectedVersion(
                $streamEventsSlice->lastEventNumber()
            );

            return $aggregateRoot;
        });
    }
}
