<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

final class InvitationRepository
{
    public function findServerIdByCode(string $code): ?int
    {
        $serverId = DB::table('invitations')
            ->where('code', $code)
            ->value('server_id');

        if ($serverId === null) {
            return null;
        }

        return (int) $serverId;
    }

    public function isUserMemberOfServer(int $serverId, int $userId): bool
    {
        return DB::table('server_members')
            ->where('server_id', $serverId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function addUserToServer(int $serverId, int $userId): void
    {
        DB::table('server_members')->insert([
            'server_id' => $serverId,
            'user_id' => $userId,
        ]);
    }

    public function createInvitation(int $serverId, string $code): void
    {
        DB::table('invitations')->insert([
            'server_id' => $serverId,
            'code' => $code,
        ]);
    }
}
