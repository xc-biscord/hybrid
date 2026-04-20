<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

final class ChannelRepository
{
    /**
     * @return array<int, array{id:int,name:string}>
     */
    public function findByServerId(int $serverId): array
    {
        return DB::table('channels')
            ->where('server_id', $serverId)
            ->orderBy('id')
            ->select('id', 'name')
            ->get()
            ->map(fn(object $r): array => (array) $r)
            ->all();
    }

    public function create(int $serverId, string $name): int
    {
        return (int) DB::table('channels')->insertGetId([
            'server_id' => $serverId,
            'name'      => $name,
        ]);
    }
}
