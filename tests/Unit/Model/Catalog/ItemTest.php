<?php

declare(strict_types=1);

namespace Tests\Unit\Model\Catalog;

use Astrocasts\Stocker\Model\Catalog\Item;
use Astrocasts\Stocker\Model\Catalog\ItemId;
use Tests\TestCase;

class ItemTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created()
    {
        $itemId = ItemId::fromString('CC778ECD-DD97-43D0-9D00-3C1010415949');
        $item = Item::create($itemId);

        $this->assertEquals(
            'CC778ECD-DD97-43D0-9D00-3C1010415949',
            $item->aggregateId()
        );
    }
}
