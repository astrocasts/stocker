<?php

declare(strict_types=1);

namespace Astrocasts\Stocker\Model\Catalog;

use Astrocasts\Stocker\Model\Common\EventSourcing\AggregateRoot;
use Astrocasts\Stocker\Model\Common\EventSourcing\Event;
use Astrocasts\Stocker\Model\Catalog\Events\ItemCreated;

final class Item extends AggregateRoot
{
    /**
     * @var ItemId
     */
    private $itemId;

    public function aggregateId(): string
    {
        return $this->itemId->toString();
    }

    public static function create(ItemId $itemId): Item
    {
        $instance = new static();
        $instance->recordThat(new ItemCreated($itemId));

        return $instance;
    }

    protected function apply(Event $event): void
    {
        switch (\get_class($event)) {
            case ItemCreated::class:
                /** @var ItemCreated $event */
                $this->itemId = $event->itemId();

                break;
        }

    }
}
