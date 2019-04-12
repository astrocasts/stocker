<?php

declare(strict_types=1);

namespace Astrocasts\Stocker\Model\Catalog;

class Name
{
    /**
     * @var string
     */
    private $value;

    public static function fromString(string $value): Name
    {
        return new self($value);
    }

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(Name $other): bool
    {
        return $this->value === $other->value;
    }
}
