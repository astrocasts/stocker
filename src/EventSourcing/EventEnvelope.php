<?php

declare(strict_types=1);

namespace Astrocasts\Stocker\EventSourcing;

use Prooph\EventStoreClient\EventId;

final class EventEnvelope
{
    /**
     * @var EventId
     */
    private $eventId;

    /**
     * @var string
     */
    private $eventType;

    /**
     * @var Event
     */
    private $event;

    /**
     * @var int
     */
    private $version;

    public static function fromEvent(Event $event, int $version): EventEnvelope
    {
        $instance = new static();
        $instance->eventId = EventId::generate();
        $instance->eventType = get_class($event);
        $instance->event = $event;
        $instance->version = $version;

        return $instance;
    }

    public function eventId(): EventId
    {
        return $this->eventId;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    public function event(): Event
    {
        return $this->event;
    }

    public function version(): int
    {
        return $this->version;
    }
}
