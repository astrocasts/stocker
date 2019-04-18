<?php

declare(strict_types=1);

namespace Astrocasts\Stocker\Model\Catalog;

use Amp\Promise;

interface Catalog
{
    public function saveItem(Item $item): Promise;
    public function getItem(ItemId $itemId): Promise;
}
