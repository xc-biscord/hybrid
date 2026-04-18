<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Server;
use Illuminate\Support\Facades\DB;

final class ServerRepository
{
    public function create(string $name, int $ownerId): int
    {
        return (int) DB::table('servers')->insertGetId([
            'name'     => $name,
            'owner_id' => $ownerId,
        ]);
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    public function findByMemberUserId(int $userId): array
    {
        return DB::table('servers as s')
            ->join('server_members as m', 'm.server_id', '=', 's.id')
            ->where('m.user_id', $userId)
            ->orderBy('s.name')
            ->select('s.id', 's.name')
            ->get()
            ->map(fn(object $r): array => (array) $r)
            ->all();
    }

    public function find(int $serverId): ?Server
    {
        $row = DB::table('servers')
            ->where('id', $serverId)
            ->select('id', 'name', 'owner_id')
            ->first();

        return $row === null ? null : Server::fromArray((array) $row);
    }
}
