<?php

declare(strict_types=1);

namespace Astrocasts\Stocker\Model\Catalog;

use Astrocasts\Stocker\EventSourcing\AggregateRoot;
use Astrocasts\Stocker\EventSourcing\Event;
use Astrocasts\Stocker\Model\Catalog\Events\ItemCreated;
use Astrocasts\Stocker\Model\Catalog\Events\ItemRenamed;

final class Item extends AggregateRoot
{
    /**
     * @var ItemId
     */
    private $itemId;

    /**
     * @var Name
     */
    private $name;

    public function aggregateId(): string
    {
        return (string) $this->itemId;
    }

    public function name(): Name
    {
        return $this->name;
    }

    public static function create(ItemId $itemId, Name $name): Item
    {
        $instance = new static();
        $instance->recordThat(new ItemCreated($itemId, $name));

        return $instance;
    }

    public function rename(Name $name): void
    {
        if ($this->name->equals($name)) {
            return;
        }

        $this->recordThat(new ItemRenamed(
            $this->itemId,
            $name
        ));
    }

    protected function apply(Event $event): void
    {
        switch (\get_class($event)) {
            case ItemCreated::class:
                /** @var ItemCreated $event */
                $this->itemId = $event->itemId();
                $this->name = $event->name();

                break;

            case ItemRenamed::class:
                /** @var ItemRenamed $event */
                $this->name = $event->name();

                break;
        }

    }
}
