<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AccountService;
use App\Validators\AccountUpdateValidator;
use DomainException;
use Illuminate\Http\JsonResponse;
use PDOException;

final class AccountController extends Controller
{
    public function __construct(
        private AccountService $accountService,
        private AccountUpdateValidator $validator,
    ) {
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    public function update(int $userId, ?array $payload): JsonResponse
    {
        $data = $this->validator->normalize($payload);

        if (!$this->validator->hasAnyUpdatableField($data)) {
            return response()->json([
                'success' => false,
                'error' => 'Aucune donnée à mettre à jour',
            ], 200);
        }

        try {
            $this->accountService->updateAccount($userId, $data);

            return response()->json([
                'success' => true,
            ], 200);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 200);
        } catch (PDOException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur SQL',
                'debug' => $e->getMessage(),
            ], 500);
        }
    }
}
