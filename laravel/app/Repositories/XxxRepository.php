<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

final class XxxRepository
{
    public function handle(int $id): array
    {
        $row = DB::selectOne('SELECT ? AS id', [$id]);

        return [
            'success' => true,
            'id' => (int) ($row->id ?? $id),
        ];
    }
}
