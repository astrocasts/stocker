<?php

declare(strict_types=1);

namespace Astrocasts\Stocker\Domain\Model\Catalog;

interface Catalog
{
    public function saveItem(): void;
    public function getItem(ItemId $itemId): ?Item;
}
