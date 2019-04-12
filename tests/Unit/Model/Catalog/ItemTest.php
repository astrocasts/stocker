<?php

declare(strict_types=1);

namespace Tests\Unit\Model\Catalog;

use Astrocasts\Stocker\EventSourcing\Testing\Scenario;
use Astrocasts\Stocker\Model\Catalog\Events\ItemCreated;
use Astrocasts\Stocker\Model\Catalog\Events\ItemRenamed;
use Astrocasts\Stocker\Model\Catalog\Item;
use Astrocasts\Stocker\Model\Catalog\ItemId;
use Astrocasts\Stocker\Model\Catalog\Name;
use Tests\TestCase;

class ItemTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_created()
    {
        $itemId = ItemId::fromString('CC778ECD-DD97-43D0-9D00-3C1010415949');
        $name = Name::fromString('elephpant');
        $item = Item::create($itemId, $name);

        $this->assertEquals(
            'CC778ECD-DD97-43D0-9D00-3C1010415949',
            $item->aggregateId()
        );
    }

    /**
     * @test
     */
    public function it_can_be_renamed()
    {
        $itemId = ItemId::fromString('CC778ECD-DD97-43D0-9D00-3C1010415949');
        $name = Name::fromString('elephpant');
        $newName = Name::fromString('plush elephpant');

        $scenario = new Scenario(
            $this,
            Item::class
        );

        $scenario
            ->withAggregateId((string) $itemId)
            ->given([
                new ItemCreated($itemId, $name),
            ])
            ->when(function (Item $item) use ($newName) {
                $item->rename($newName);
            })
            ->then([
                new ItemRenamed($itemId, $newName),
            ])
        ;
    }

    /**
     * @test
     */
    public function it_does_not_rename_the_same_name_again()
    {
        $itemId = ItemId::fromString('CC778ECD-DD97-43D0-9D00-3C1010415949');
        $name = Name::fromString('elephpant');

        $scenario = new Scenario(
            $this,
            Item::class
        );

        $scenario
            ->withAggregateId((string) $itemId)
            ->given([
                new ItemCreated($itemId, $name),
            ])
            ->when(function (Item $item) use ($name) {
                $item->rename($name);
            })
            ->then([])
        ;
    }

    /**
     * @test
     */
    public function it_can_remember_its_name()
    {
        $itemId = ItemId::fromString('CC778ECD-DD97-43D0-9D00-3C1010415949');
        $name = Name::fromString('elephpant');
        $newName = Name::fromString('plush elephpant');

        $scenario = new Scenario(
            $this,
            Item::class
        );

        $scenario
            ->withAggregateId((string) $itemId)
            ->given([
                new ItemCreated($itemId, $name),
                new ItemRenamed($itemId, $newName),
            ])
            ->when(function (Item $item) use ($newName) {
                $item->rename($newName);
            })
            ->then([])
        ;
    }
}
