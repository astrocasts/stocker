<?php

declare(strict_types=1);

namespace Astrocasts\Stocker\Model\Catalog;

final class ItemId
{
    /**
     * @var string
     */
    private $uuid;

    public static function generate(): ItemId
    {
        return new self((string) \Ramsey\Uuid\Uuid::uuid4());
    }

    public static function fromString(string $itemId): ItemId
    {
        return new self($itemId);
    }

    private function __construct(string $itemId)
    {
        $this->uuid = $itemId;
    }

    public function __toString(): string
    {
        return $this->uuid;
    }

    public function equals(ItemId $other): bool
    {
        return $this->uuid === $other->uuid;
    }
}
