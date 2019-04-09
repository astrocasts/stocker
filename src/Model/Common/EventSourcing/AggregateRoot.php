<?php

declare(strict_types=1);

namespace Astrocasts\Stocker\Model\Common\EventSourcing;

use Prooph\EventStoreClient\ExpectedVersion;

abstract class AggregateRoot
{
    /** @var int */
    protected $expectedVersion = ExpectedVersion::EMPTY_STREAM;

    /**
     * List of events that are not committed to the EventStore
     *
     * @var EventEnvelope[]
     */
    protected $recordedEvents = [];

    protected function __construct()
    {
    }

    public function expectedVersion(): int
    {
        return $this->expectedVersion;
    }

    public function setExpectedVersion(int $version): void
    {
        $this->expectedVersion = $version;
    }

    /**
     * Get pending events and reset stack
     *
     * @return EventEnvelope[]
     */
    public function popRecordedEvents(): array
    {
        $pendingEvents = $this->recordedEvents;

        $this->recordedEvents = [];

        return $pendingEvents;
    }

    /**
     * Record an aggregate changed event
     */
    protected function recordThat(Event $event): void
    {
        $this->recordedEvents[] = EventEnvelope::fromEvent($event);

        $this->apply($event);
    }

    public static function reconstituteFromHistory(array $historyEvents): self
    {
        $instance = new static();
        $instance->replay($historyEvents);

        return $instance;
    }

    /**
     * Replay past events
     *
     * @param EventEnvelope[]
     */
    public function replay(array $historyEvents): void
    {
        foreach ($historyEvents as $pastEvent) {
            /** @var EventEnvelope $pastEvent */
            $this->apply($pastEvent->event());
        }
    }

    abstract public function aggregateId(): string;

    abstract protected function apply(Event $event): void;
}
