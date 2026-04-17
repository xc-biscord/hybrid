<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AccountService;
use App\Validators\AccountUpdateValidator;
use DomainException;
use PDOException;

final class AccountController
{
    public function __construct(
        private AccountService $accountService,
        private AccountUpdateValidator $validator,
    ) {
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array{statusCode:int,payload:array<string,mixed>}
     */
    public function update(int $userId, ?array $payload): array
    {
        $data = $this->validator->normalize($payload);

        if (!$this->validator->hasAnyUpdatableField($data)) {
            return [
                'statusCode' => 200,
                'payload' => ['success' => false, 'error' => 'Aucune donnée à mettre à jour'],
            ];
        }

        try {
            $this->accountService->updateAccount($userId, $data);

            return [
                'statusCode' => 200,
                'payload' => ['success' => true],
            ];
        } catch (DomainException $e) {
            return [
                'statusCode' => 200,
                'payload' => ['success' => false, 'error' => $e->getMessage()],
            ];
        } catch (PDOException $e) {
            return [
                'statusCode' => 500,
                'payload' => [
                    'success' => false,
                    'error' => 'Erreur SQL',
                    'debug' => $e->getMessage(),
                ],
            ];
        }
    }
}
