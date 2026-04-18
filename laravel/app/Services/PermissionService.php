<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PermissionService
{
    public function isP1(int $userId): bool
    {
        return DB::table('global_permissions')
            ->where('user_id', $userId)
            ->exists();
    }
}
