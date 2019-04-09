<?php

declare(strict_types=1);

namespace Astrocasts\Stocker\Model\Catalog\Events;

use Astrocasts\Stocker\Model\Catalog\ItemId;
use Astrocasts\Stocker\Model\Common\EventSourcing\Event;

final class ItemCreated implements Event
{
    /**
     * @var ItemId
     */
    private $itemId;

    public function __construct(ItemId $itemId)
    {
        $this->itemId = $itemId;
    }

    public function itemId(): ItemId
    {
        return $this->itemId;
    }
}
