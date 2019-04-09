<?php

declare(strict_types=1);

namespace Astrocasts\Stocker\Model\Common\EventSourcing;

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

    public static function fromEvent(Event $event): EventEnvelope
    {
        $instance = new static();
        $instance->eventId = EventId::generate();
        $instance->eventType = get_class($event);
        $instance->event = $event;

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
}
