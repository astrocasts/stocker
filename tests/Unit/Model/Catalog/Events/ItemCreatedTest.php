<?php

namespace Tests\Unit\Model\Catalog\Events;

use Astrocasts\Stocker\Model\Catalog\Events\ItemCreated;
use Astrocasts\Stocker\Model\Catalog\ItemId;
use Astrocasts\Stocker\Model\Catalog\Name;
use Tests\TestCase;
use Tests\Unit\TestsSerialization;

class ItemCreatedTest extends TestCase
{
    use TestsSerialization;

    /** @test */
    public function it_serializes()
    {
        $itemId = ItemId::fromString('9042648B-B6A3-4990-AF63-27F79C280D4A');
        $name = Name::fromString('elephpant');
        $itemCreated = new ItemCreated($itemId, $name);

        $this->assertSerializerRoundTrip(ItemCreated::class, $itemCreated);
    }

    /** @test */
    public function it_deserializes()
    {
        $json = <<<'EOT'
{
    "itemId": "9042648B-B6A3-4990-AF63-27F79C280D4A",
    "name": "elephpant"
}    
EOT;

        /** @var ItemCreated $itemCreated */
        $itemCreated = $this->deserialize(ItemCreated::class, $json);

        $this->assertEquals('9042648B-B6A3-4990-AF63-27F79C280D4A', (string) $itemCreated->itemId());
        $this->assertEquals('elephpant', (string) $itemCreated->name());
    }
}
