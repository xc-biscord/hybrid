<?php

declare(strict_types=1);

namespace App\Models;

final class Server
{
    public function __construct(
        public int $id,
        public string $name,
        public int $ownerId,
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            (int) ($row['id'] ?? 0),
            (string) ($row['name'] ?? ''),
            (int) ($row['owner_id'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'owner_id' => $this->ownerId,
        ];
    }
}
