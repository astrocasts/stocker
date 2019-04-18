<?php

namespace Astrocasts\Stocker\EventSourcing;


use Prooph\EventStoreClient\EventData;
use Prooph\EventStoreClient\ResolvedEvent;

class MessageTransformer
{
    public function toEventData(EventEnvelope $eventEnvelope): EventData
    {
        //
    }

    public function toDomainEvent(ResolvedEvent $eventData): EventEnvelope
    {
        //
    }
}
