<?php

namespace Astrocasts\Stocker\Infrastructure\Persistence\EventStore;

use Amp\Promise;
use Astrocasts\Stocker\EventSourcing\AggregateRepository;
use Astrocasts\Stocker\Model\Catalog\Catalog;
use Astrocasts\Stocker\Model\Catalog\Item;
use Astrocasts\Stocker\Model\Catalog\ItemId;

class CatalogEventStoreRepository extends AggregateRepository implements Catalog
{
    public function saveItem(Item $item): Promise
    {
        return $this->saveAggregateRoot($item);
    }

    public function getItem(ItemId $itemId): Promise
    {
        return $this->getAggregateRoot((string) $itemId);
    }
}
