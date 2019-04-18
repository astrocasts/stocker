<?php

namespace Tests\Unit;

use Astrocasts\Stocker\Serialization\JsonSerializer;

trait TestsSerialization
{
    protected function assertSerializerRoundTrip(string $type, object $instance): void
    {
        $jsonSerializer = new JsonSerializer();

        $jsonString = $jsonSerializer->serialize($instance);
        $deserializedInstance = $jsonSerializer->deserialize($type, $jsonString);

        $this->assertEquals($instance, $deserializedInstance);
    }

    protected function deserialize(string $type, string $jsonString): object
    {
        $jsonSerializer = new JsonSerializer();

        return $jsonSerializer->deserialize($type, $jsonString);
    }
}
