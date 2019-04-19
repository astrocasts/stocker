<?php

namespace Astrocasts\Stocker\EventSourcing;

use Astrocasts\Stocker\Serialization\JsonSerializer;
use Prooph\EventStoreClient\EventData;
use Prooph\EventStoreClient\EventId;
use Prooph\EventStoreClient\ResolvedEvent;

class MessageTransformer
{
    /**
     * @var JsonSerializer
     */
    private $serializer;

    public function __construct(JsonSerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    public function toEventData(EventEnvelope $eventEnvelope): EventData
    {
        if ($eventId = $eventEnvelope->eventId()) {
            $eventId = EventId::fromString($eventId);
        } else {
            $eventId = EventId::generate();
        }

        $event = $eventEnvelope->event();

        return new EventData(
            $eventId,
            static::resolveFromClassName(get_class($event)),
            true,
            $this->serializer->serialize($event)
        );
    }

    public function toDomainEvent(ResolvedEvent $eventData): EventEnvelope
    {
        return EventEnvelope::fromEvent(
            $this->serializer->deserialize(
                self::resolveFromContractName($eventData->event()->eventType()),
                $eventData->event()->data()
            ),
            $eventData->originalEventNumber()
        );
        /*
        $event = $eventEnvelope->event();
        $eventType = static::resolveFromClassName(get_class($event));

        $payload = Json::decode($event->data());

        $class = $this->map[$eventType];

        return $class::from($event->eventId(), $payload);
        */
    }

    private static function resolveFromContractName(string $contractName): string
    {
        return str_replace('.', '\\', $contractName);
    }

    private static function resolveFromClassName(string $className): string
    {
        return str_replace('\\', '.', trim($className, '\\'));
    }
}
