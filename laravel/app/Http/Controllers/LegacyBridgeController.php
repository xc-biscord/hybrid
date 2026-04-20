<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
            case 'create_channel':
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

        $_POST = $input;

        require base_path(sprintf('../api/%s.php', $endpoint));

        exit;
    }
}
