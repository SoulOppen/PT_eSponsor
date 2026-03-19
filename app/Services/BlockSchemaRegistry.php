<?php

namespace App\Services;

use InvalidArgumentException;

class BlockSchemaRegistry
{
    /**
     * @return array<string, array{label: string, icon?: string, fields: list<array<string, mixed>>}>
     */
    public function all(): array
    {
        return config('blocks.schemas', []);
    }

    /**
     * @return array{label: string, icon?: string, fields: list<array<string, mixed>>}
     */
    public function get(string $type): array
    {
        $schemas = $this->all();

        if (! array_key_exists($type, $schemas)) {
            throw new InvalidArgumentException("Unknown block type: {$type}");
        }

        return $schemas[$type];
    }
}
