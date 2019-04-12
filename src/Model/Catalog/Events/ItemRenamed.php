<?php

declare(strict_types=1);

namespace Astrocasts\Stocker\Model\Catalog\Events;

use Astrocasts\Stocker\Model\Catalog\ItemId;
use Astrocasts\Stocker\EventSourcing\Event;
use Astrocasts\Stocker\Model\Catalog\Name;

class ItemRenamed implements Event
{
    /**
     * @var ItemId
     */
    private $itemId;

    /**
     * @var Name
     */
    private $name;

    public function __construct(ItemId $itemId, Name $name)
    {
        $this->itemId = $itemId;
        $this->name = $name;
    }

    public function itemId(): ItemId
    {
        return $this->itemId;
    }

    public function name(): Name
    {
        return $this->name;
    }
}
