<?php

declare(strict_types=1);

namespace App\Models;

final class ServerMember
{
    public function __construct(
        public int $serverId,
        public int $userId,
        public string $role,
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            (int) ($row['server_id'] ?? 0),
            (int) ($row['user_id'] ?? 0),
            (string) ($row['role'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'server_id' => $this->serverId,
            'user_id' => $this->userId,
            'role' => $this->role,
        ];
    }
}
