<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class LegacyBridgeController extends Controller
{
    public function handle(Request $request, string $endpoint)
    {
        Log::info('legacy bridge endpoint called', [
            'endpoint' => $endpoint,
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        switch ($endpoint) {
            case 'get_servers':
                Log::info('legacy bridge dispatch', ['endpoint' => $endpoint, 'target' => 'laravel']);
                return app(ServerController::class)->index((int) $request->session()->get('user_id', 0));

            case 'get_server_name':
                Log::info('legacy bridge dispatch', ['endpoint' => $endpoint, 'target' => 'laravel']);
                return app(ServerController::class)->showName((int) $request->query('id', 0));

            case 'get_channels':
                Log::info('legacy bridge dispatch', ['endpoint' => $endpoint, 'target' => 'laravel']);
                return app(ChannelController::class)->index(
                    (int) $request->session()->get('user_id', 0),
                    (int) $request->query('server_id', 0),
                );

            case 'get_messages':
                Log::info('legacy bridge dispatch', ['endpoint' => $endpoint, 'target' => 'laravel']);
                $userId = $this->authenticatedNativeUserId();
                if ($userId instanceof JsonResponse) {
                    return $userId;
                }

                return app(MessageController::class)->index(
                    $userId,
                    (int) $request->query('channel_id', 0),
                );

            case 'create_server':
                Log::info('legacy bridge dispatch', ['endpoint' => $endpoint, 'target' => 'laravel']);
                $methodError = $this->validateCreateServerMethod($request);
                if ($methodError !== null) {
                    return $methodError;
                }

                $userId = $this->authenticatedNativeUserId();
                if ($userId instanceof JsonResponse) {
                    return $userId;
                }

                $payload = $this->extractCreateServerJsonInput($request);
                if ($payload instanceof JsonResponse) {
                    return $payload;
                }

                return app(ServerController::class)->create(
                    $userId,
                    $payload,
                );

            case 'create_channel':
                Log::info('legacy bridge dispatch', ['endpoint' => $endpoint, 'target' => 'laravel']);
                $methodError = $this->validateCreateChannelMethod($request);
                if ($methodError !== null) {
                    return $methodError;
                }

                $userId = $this->authenticatedNativeUserId();
                if ($userId instanceof JsonResponse) {
                    return $userId;
                }

                $payload = $this->extractCreateChannelJsonInput($request);
                if ($payload instanceof JsonResponse) {
                    return $payload;
                }

                return app(ChannelController::class)->create(
                    $userId,
                    $payload,
                );

            case 'send_message':
                Log::info('legacy bridge dispatch', ['endpoint' => $endpoint, 'target' => 'laravel']);
                $methodError = $this->validateSendMessageMethod($request);
                if ($methodError !== null) {
                    return $methodError;
                }

                $userId = $this->authenticatedNativeUserId();
                if ($userId instanceof JsonResponse) {
                    return $userId;
                }

                $payload = $this->extractSendMessageJsonInput($request);
                if ($payload instanceof JsonResponse) {
                    return $payload;
                }

                return app(MessageController::class)->create($userId, $payload);

            case 'create_invite':
                Log::info('legacy bridge dispatch', ['endpoint' => $endpoint, 'target' => 'laravel']);
                $serverId = $this->optionalNumericPostValue($request, 'server_id');

                return app(InvitationController::class)->create(
                    $this->optionalNativeUserId(),
                    $serverId,
                );

            case 'accept_invite':
                Log::info('legacy bridge dispatch', ['endpoint' => $endpoint, 'target' => 'laravel']);
                $code = trim((string) $request->request->get('code', ''));

                return app(InvitationController::class)->accept(
                    $this->optionalNativeUserId(),
                    $code,
                );

            default:
                abort(404);
        }
    }

    private function forwardToLegacy(Request $request, string $endpoint): never
    {
        Log::info('legacy bridge dispatch', ['endpoint' => $endpoint, 'target' => 'legacy']);

        $query = $request->query->all();
        if (is_array($query)) {
            $_GET = $query;
        }

        $_POST = $this->extractInput($request);

        require base_path(sprintf('../api/%s.php', $endpoint));

        exit;
    }

    /**
     * @return array<string, mixed>
     */
    private function extractInput(Request $request): array
    {
        $input = $request->request->all();
        if (!is_array($input)) {
            $input = [];
        }

        if ($request->isJson()) {
            $json = $request->json()->all();
            if (is_array($json)) {
                $input = array_merge($input, $json);
            }
        }

        return $input;
    }

    private function validateCreateServerMethod(Request $request): ?JsonResponse
    {
        if ($request->method() === 'POST') {
            return null;
        }

        return new JsonResponse([
            'success' => false,
            'error' => 'Méthode non autorisée',
        ], 405);
    }

    private function validateCreateChannelMethod(Request $request): ?JsonResponse
    {
        if ($request->method() === 'POST') {
            return null;
        }

        return new JsonResponse([
            'success' => false,
            'error' => 'Méthode non autorisée',
        ], 405);
    }

    private function validateSendMessageMethod(Request $request): ?JsonResponse
    {
        if ($request->method() === 'POST') {
            return null;
        }

        return new JsonResponse([
            'success' => false,
            'error' => 'Méthode non autorisée',
        ], 405);
    }

    private function authenticatedNativeUserId(): int|JsonResponse
    {
        $this->startNativeSession();

        if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
            return (int) $_SESSION['user_id'];
        }

        return new JsonResponse([
            'success' => false,
            'error' => 'Non authentifié',
        ], 401);
    }

    private function optionalNativeUserId(): ?int
    {
        if (session_status() === PHP_SESSION_NONE && isset($_COOKIE[session_name()])) {
            $this->startNativeSession();
        }

        if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
            return (int) $_SESSION['user_id'];
        }

        return null;
    }

    private function startNativeSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function extractCreateServerJsonInput(Request $request): array|JsonResponse
    {
        $raw = $request->getContent();

        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'JSON invalide',
            ], 400);
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function extractCreateChannelJsonInput(Request $request): array|JsonResponse
    {
        $raw = $request->getContent();

        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'JSON invalide',
            ], 400);
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>|JsonResponse
     */
    private function extractSendMessageJsonInput(Request $request): array|JsonResponse
    {
        $raw = $request->getContent();

        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'JSON invalide',
            ], 400);
        }

        return $decoded;
    }

    private function optionalNumericPostValue(Request $request, string $key): ?int
    {
        $value = $request->request->get($key);

        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }
}
