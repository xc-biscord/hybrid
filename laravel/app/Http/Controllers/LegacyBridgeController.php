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
                return app(MessageController::class)->index(
                    (int) $request->session()->get('user_id', 0),
                    (int) $request->query('channel_id', 0),
                );

            case 'create_server':
                Log::info('legacy bridge dispatch', ['endpoint' => $endpoint, 'target' => 'laravel']);
                $methodError = $this->validateCreateServerMethod($request);
                if ($methodError !== null) {
                    return $methodError;
                }

                $payload = $this->extractCreateServerJsonInput($request);
                if ($payload instanceof JsonResponse) {
                    return $payload;
                }

                return app(ServerController::class)->create(
                    (int) $request->session()->get('user_id', 0),
                    $payload,
                );

            case 'create_channel':
                Log::info('legacy bridge dispatch', ['endpoint' => $endpoint, 'target' => 'laravel']);
                $methodError = $this->validateCreateChannelMethod($request);
                if ($methodError !== null) {
                    return $methodError;
                }

                $authError = $this->validateSessionAuthentication($request);
                if ($authError !== null) {
                    return $authError;
                }

                $payload = $this->extractCreateChannelJsonInput($request);
                if ($payload instanceof JsonResponse) {
                    return $payload;
                }

                return app(ChannelController::class)->create(
                    (int) $request->session()->get('user_id', 0),
                    $payload,
                );

            case 'send_message':
            case 'create_invite':
            case 'accept_invite':
                return $this->forwardToLegacy($request, $endpoint);

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

    private function validateSessionAuthentication(Request $request): ?JsonResponse
    {
        if ($request->session()->has('user_id')) {
            return null;
        }

        return new JsonResponse([
            'success' => false,
            'error' => 'Non authentifié',
        ], 401);
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
}
