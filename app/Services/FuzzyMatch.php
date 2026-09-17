<?php

namespace App\Services;

use JsonSerializable;

final readonly class FuzzyMatch implements JsonSerializable
{
    public function __construct(
        public string $query,
        public string $matched,
        public string $column,
        public int $distance,
    ) {}

    /**
     * @return array{query: string, matched: string, column: string, distance: int}
     */
    public function jsonSerialize(): array
    {
        return [
            'query' => $this->query,
            'matched' => $this->matched,
            'column' => $this->column,
            'distance' => $this->distance,
        ];
    }
}
