<?php

declare(strict_types=1);

namespace Tests\Unit\Astrocasts\Stocker\Domain\Model\Catalog\Events;

use Astrocasts\Stocker\Domain\Model\Catalog\ItemId;
use Astrocasts\Stocker\Domain\Model\Common\EventSourcing\Event;

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
