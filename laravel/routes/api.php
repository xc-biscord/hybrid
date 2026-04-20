<?php

declare(strict_types=1);

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DmController;
use App\Http\Controllers\LegacyBridgeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\RoleModerationController;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\SendDmRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Register
|--------------------------------------------------------------------------
*/
Route::post('/register.php', function (RegisterRequest $request, AuthController $controller) {
    return $controller->register($request->validated());
});

Route::middleware(['auth.session'])->group(function (): void {
    Route::get('/get_servers.php', [LegacyBridgeController::class, 'handle'])
        ->defaults('endpoint', 'get_servers');
    Route::post('/create_server.php', [LegacyBridgeController::class, 'handle'])
        ->defaults('endpoint', 'create_server');
    Route::get('/get_server_name.php', [LegacyBridgeController::class, 'handle'])
        ->defaults('endpoint', 'get_server_name');
    Route::get('/get_channels.php', [LegacyBridgeController::class, 'handle'])
        ->defaults('endpoint', 'get_channels');
    Route::post('/create_channel.php', [LegacyBridgeController::class, 'handle'])
        ->defaults('endpoint', 'create_channel');
    Route::get('/get_messages.php', [LegacyBridgeController::class, 'handle'])
        ->defaults('endpoint', 'get_messages');
    Route::post('/send_message.php', [LegacyBridgeController::class, 'handle'])
        ->defaults('endpoint', 'send_message');
    Route::post('/create_invite.php', [LegacyBridgeController::class, 'handle'])
        ->defaults('endpoint', 'create_invite');
    Route::post('/accept_invite.php', [LegacyBridgeController::class, 'handle'])
        ->defaults('endpoint', 'accept_invite');
});

Route::middleware(['auth.session'])->group(function (): void {
    /*
    |--------------------------------------------------------------------------
    | Servers / Channels / Messages migrated via LegacyBridgeController
    |--------------------------------------------------------------------------
    */

    Route::any('/delete_message.php', function (Request $request, MessageController $controller) {
        $userId = (int) $request->session()->get('user_id', 0);
        $payload = $request->json()->all();
        if (!is_array($payload)) {
            $payload = [];
        }

        return $controller->delete($userId, $payload);
    });

    /*
    |--------------------------------------------------------------------------
    | DM
    |--------------------------------------------------------------------------
    */
    Route::post('/start_dm.php', function (Request $request, DmController $controller) {
        $userId = (int) $request->session()->get('user_id', 0);
        return $controller->start($userId, $request->json()->all());
    });

    Route::get('/get_dm_messages.php', function (Request $request, DmController $controller) {
        $userId = (int) $request->session()->get('user_id', 0);
        $conversationId = (int) $request->query('conversation_id', 0);

        return $controller->messages($userId, $conversationId);
    });

    Route::post('/send_dm.php', function (SendDmRequest $request, DmController $controller) {
        $userId = (int) $request->session()->get('user_id', 0);
        return $controller->send($userId, $request->validated());
    });

    Route::get('/get_dm_notifications.php', function (Request $request, DmController $controller) {
        $userId = (int) $request->session()->get('user_id', 0);
        return $controller->notifications($userId);
    });

    /*
    |--------------------------------------------------------------------------
    | Moderation
    |--------------------------------------------------------------------------
    */
    Route::get('/get_my_server_role.php', function (Request $request, RoleModerationController $controller) {
        $userId = (int) $request->session()->get('user_id', 0);
        $rawServerId = $request->query('server_id');
        $serverId = is_numeric($rawServerId) ? (int) $rawServerId : null;

        return $controller->getMyServerRole($userId, $serverId);
    });

    Route::get('/get_users_in_server.php', function (Request $request, RoleModerationController $controller) {
        $userId = (int) $request->session()->get('user_id', 0);
        $serverId = (int) $request->query('server_id', 0);

        return $controller->listUsersInServer($userId, $serverId);
    });

    Route::any('/set_member_role.php', function (Request $request, RoleModerationController $controller) {
        $userId = (int) $request->session()->get('user_id', 0);
        return $controller->setMemberRole($userId, $request->json()->all());
    });

    Route::any('/kick_member.php', function (Request $request, RoleModerationController $controller) {
        $userId = (int) $request->session()->get('user_id', 0);
        return $controller->kickMember($userId, $request->json()->all());
    });

    /*
    |--------------------------------------------------------------------------
    | Admin
    |--------------------------------------------------------------------------
    */
    Route::middleware(['p1.only'])->group(function (): void {
        Route::get('/get_all_users.php', function (Request $request, AdminUserController $controller) {
            $userId = (int) $request->session()->get('user_id', 0);
            return $controller->listUsers($userId);
        });

        Route::get('/get_user_servers.php', function (Request $request, AdminUserController $controller) {
            $userId = (int) $request->session()->get('user_id', 0);
            $targetUserId = (int) $request->query('user_id', 0);

            return $controller->listUserServers($userId, $targetUserId);
        });

        Route::any('/ban_user.php', function (Request $request) {
            $targetUserId = (int) ($request->json('user_id') ?? 0);
            if ($targetUserId <= 0) {
                return response()->json(['success' => false, 'error' => 'user_id invalide']);
            }

            try {
                DB::table('server_members')->where('user_id', $targetUserId)->delete();
                DB::table('messages')->where('user_id', $targetUserId)->delete();
                DB::table('profiles')->where('user_id', $targetUserId)->delete();
                DB::table('global_permissions')->where('user_id', $targetUserId)->delete();
                DB::table('users')->where('id', $targetUserId)->delete();

                return response()->json(['success' => true]);
            } catch (\PDOException $e) {
                return response()->json(['success' => false, 'error' => 'Erreur DB : ' . $e->getMessage()]);
            }
        });
    });
});
