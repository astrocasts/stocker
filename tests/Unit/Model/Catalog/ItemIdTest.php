<?php

declare(strict_types=1);

namespace Tests\Unit\Model\Catalog;

use Astrocasts\Stocker\Model\Catalog\ItemId;
use NaiveSerializer\JsonSerializer;
use Tests\TestCase;

class ItemIdTest extends TestCase
{
    /**
     * @test
     */
    public function it_serializes()
    {
        $itemId = ItemId::fromString('D5550FB7-3823-4A31-99FF-21F0A67B9410');
        $serializer = new JsonSerializer();

        $data = $serializer->serialize($itemId);

        $this->assertEquals([
            'uuid' => 'D5550FB7-3823-4A31-99FF-21F0A67B9410',
        ], json_decode($data, true));
    }

    /**
     * @test
     */
    public function it_derializes()
    {
        $data = '{"uuid":"D5550FB7-3823-4A31-99FF-21F0A67B9410"}';
        $serializer = new JsonSerializer();

        /** @var ItemId $itemId */
        $itemId = $serializer->deserialize(ItemId::class, $data);

        $this->assertTrue($itemId->equals(ItemId::fromString('D5550FB7-3823-4A31-99FF-21F0A67B9410')));
    }

    /**
     * @test
     */
    public function it_converts_to_string()
    {
        $data = '{"uuid":"D5550FB7-3823-4A31-99FF-21F0A67B9410"}';
        $serializer = new JsonSerializer();

        /** @var ItemId $itemId */
        $itemId = $serializer->deserialize(ItemId::class, $data);

        $this->assertEquals('D5550FB7-3823-4A31-99FF-21F0A67B9410', (string) $itemId);
    }
}
