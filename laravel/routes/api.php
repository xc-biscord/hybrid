<?php

declare(strict_types=1);

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\Api\BanUserController;
use App\Http\Controllers\Api\GetProfileController;
use App\Http\Controllers\Api\GetUserProfileController;
use App\Http\Controllers\Api\Passkey\LoginMethodsController;
use App\Http\Controllers\Api\Passkey\PasskeyAuthController;
use App\Http\Controllers\Api\Passkey\PasskeyManagementController;
use App\Http\Controllers\Api\Passkey\PasskeyRegisterController;
use App\Http\Controllers\Api\UpdateProfileController;
use App\Http\Controllers\Api\XxxController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\DmController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\RoleModerationController;
use App\Http\Controllers\ServerController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Legacy /api/*.php endpoints — single Laravel runtime (Phase 6)
|--------------------------------------------------------------------------
|
| Each route reproduces, byte-for-byte on the observable HTTP contract, the
| behaviour of the historical api/*.php wrapper it replaces. Authentication is
| session based via the native PHP $_SESSION superglobal (the built-in server
| router starts the session before Laravel boots, mirroring the old
| config.php). Helpers below mirror api/bootstrap.php exactly.
|
*/

$requireAuthUserId = static function (): int|JsonResponse {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['user_id']) || ! is_numeric($_SESSION['user_id'])) {
        return new JsonResponse(['success' => false, 'error' => 'Non authentifié'], 401);
    }

    return (int) $_SESSION['user_id'];
};

$requireMethod = static function (Request $request, string $method): ?JsonResponse {
    if ($request->getMethod() !== $method) {
        return new JsonResponse(['success' => false, 'error' => 'Méthode non autorisée'], 405);
    }

    return null;
};

$jsonInput = static function (Request $request): array|JsonResponse {
    $raw = $request->getContent();
    if ($raw === '' || $raw === false) {
        return [];
    }

    $data = json_decode($raw, true);
    if (! is_array($data)) {
        return new JsonResponse(['success' => false, 'error' => 'JSON invalide'], 400);
    }

    return $data;
};

$optionalNativeUserId = static function (): ?int {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
        return (int) $_SESSION['user_id'];
    }

    return null;
};

/*
|--------------------------------------------------------------------------
| Auth / session
|--------------------------------------------------------------------------
*/

Route::any('/login.php', function (Request $request) use ($requireMethod, $jsonInput) {
    if ($error = $requireMethod($request, 'POST')) {
        return $error;
    }
    $data = $jsonInput($request);
    if ($data instanceof JsonResponse) {
        return $data;
    }

    return app(AuthController::class)->login($data);
});

Route::any('/register.php', function (Request $request) use ($requireMethod, $jsonInput) {
    if ($error = $requireMethod($request, 'POST')) {
        return $error;
    }
    $data = $jsonInput($request);
    if ($data instanceof JsonResponse) {
        return $data;
    }

    return app(AuthController::class)->register($data);
});

Route::any('/check_auth.php', function () {
    return app(AuthController::class)->checkAuth();
});

Route::any('/auth.php', function () {
    $response = app(AuthController::class)->auth();
    if ($response instanceof JsonResponse) {
        return $response;
    }

    // Legacy success: empty body, JSON content type.
    return response('', 200)->header('Content-Type', 'application/json; charset=utf-8');
});

Route::any('/logout.php', function () {
    // Legacy behaviour: session_unset/destroy + 302 redirect to /index.html, then exit.
    app(AuthController::class)->logout();
});

/*
|--------------------------------------------------------------------------
| Passkeys / WebAuthn (PoC expérimental — additif, ne touche pas login.php)
|--------------------------------------------------------------------------
|
| Login en deux étapes :
|   1. /api/login_methods.php       -> méthodes disponibles (password/passkey)
|   2a. /api/login.php              -> voie mot de passe (INCHANGÉE, fallback)
|   2b. /api/passkey_login_*.php    -> voie passkey (challenge puis vérification)
|
| Gestion depuis le profil (utilisateur connecté) :
|   /api/passkey_register_options.php, /api/passkey_register_verify.php,
|   /api/passkey_list.php, /api/passkey_delete.php
|
*/

Route::any('/login_methods.php', function (Request $request) use ($requireMethod) {
    if ($error = $requireMethod($request, 'POST')) {
        return $error;
    }

    return app(LoginMethodsController::class)->handle($request);
});

Route::any('/passkey_login_options.php', function (Request $request) use ($requireMethod) {
    if ($error = $requireMethod($request, 'POST')) {
        return $error;
    }

    return app(PasskeyAuthController::class)->options($request);
});

Route::any('/passkey_login_verify.php', function (Request $request) use ($requireMethod) {
    if ($error = $requireMethod($request, 'POST')) {
        return $error;
    }

    return app(PasskeyAuthController::class)->verify($request);
});

Route::any('/passkey_register_options.php', function (Request $request) use ($requireMethod) {
    if ($error = $requireMethod($request, 'POST')) {
        return $error;
    }

    return app(PasskeyRegisterController::class)->options($request);
});

Route::any('/passkey_register_verify.php', function (Request $request) use ($requireMethod) {
    if ($error = $requireMethod($request, 'POST')) {
        return $error;
    }

    return app(PasskeyRegisterController::class)->verify($request);
});

Route::any('/passkey_list.php', function (Request $request) {
    return app(PasskeyManagementController::class)->list($request);
});

Route::any('/passkey_delete.php', function (Request $request) use ($requireMethod) {
    if ($error = $requireMethod($request, 'POST')) {
        return $error;
    }

    return app(PasskeyManagementController::class)->delete($request);
});

/*
|--------------------------------------------------------------------------
| Profile / account
|--------------------------------------------------------------------------
*/

Route::any('/get_profile.php', function (Request $request) {
    return app(GetProfileController::class)->handle($request);
});

Route::any('/get_user_profile.php', function (Request $request) {
    return app(GetUserProfileController::class)->handle($request);
});

Route::any('/update_profile.php', function (Request $request) {
    return app(UpdateProfileController::class)->handle($request);
});

Route::any('/update_account.php', function (Request $request) use ($jsonInput) {
    if (! isset($_SESSION['user_id'])) {
        return new JsonResponse(['success' => false, 'error' => 'Non connecté'], 200);
    }

    $payload = $jsonInput($request);
    if ($payload instanceof JsonResponse) {
        return $payload;
    }

    return app(AccountController::class)->update((int) $_SESSION['user_id'], $payload);
});

/*
|--------------------------------------------------------------------------
| Servers / channels / messages
|--------------------------------------------------------------------------
*/

Route::any('/get_servers.php', function () use ($requireAuthUserId) {
    $userId = $requireAuthUserId();
    if ($userId instanceof JsonResponse) {
        return $userId;
    }

    return app(ServerController::class)->index($userId);
});

Route::any('/get_server_name.php', function (Request $request) use ($requireAuthUserId) {
    $userId = $requireAuthUserId();
    if ($userId instanceof JsonResponse) {
        return $userId;
    }

    return app(ServerController::class)->showName((int) $request->query('id', 0));
});

Route::any('/create_server.php', function (Request $request) use ($requireMethod, $requireAuthUserId, $jsonInput) {
    if ($error = $requireMethod($request, 'POST')) {
        return $error;
    }
    $userId = $requireAuthUserId();
    if ($userId instanceof JsonResponse) {
        return $userId;
    }
    $data = $jsonInput($request);
    if ($data instanceof JsonResponse) {
        return $data;
    }

    return app(ServerController::class)->create($userId, $data);
});

Route::any('/get_channels.php', function (Request $request) use ($requireAuthUserId) {
    $userId = $requireAuthUserId();
    if ($userId instanceof JsonResponse) {
        return $userId;
    }

    return app(ChannelController::class)->index($userId, (int) $request->query('server_id', 0));
});

Route::any('/create_channel.php', function (Request $request) use ($requireMethod, $requireAuthUserId, $jsonInput) {
    if ($error = $requireMethod($request, 'POST')) {
        return $error;
    }
    $userId = $requireAuthUserId();
    if ($userId instanceof JsonResponse) {
        return $userId;
    }
    $data = $jsonInput($request);
    if ($data instanceof JsonResponse) {
        return $data;
    }

    return app(ChannelController::class)->create($userId, $data);
});

Route::any('/get_messages.php', function (Request $request) use ($requireAuthUserId) {
    $userId = $requireAuthUserId();
    if ($userId instanceof JsonResponse) {
        return $userId;
    }

    return app(MessageController::class)->index($userId, (int) $request->query('channel_id', 0));
});

Route::any('/send_message.php', function (Request $request) use ($requireMethod, $requireAuthUserId, $jsonInput) {
    if ($error = $requireMethod($request, 'POST')) {
        return $error;
    }
    $userId = $requireAuthUserId();
    if ($userId instanceof JsonResponse) {
        return $userId;
    }
    $data = $jsonInput($request);
    if ($data instanceof JsonResponse) {
        return $data;
    }

    return app(MessageController::class)->create($userId, $data);
});

Route::any('/delete_message.php', function (Request $request) use ($requireAuthUserId) {
    $userId = $requireAuthUserId();
    if ($userId instanceof JsonResponse) {
        return $userId;
    }

    $data = json_decode($request->getContent() ?: '', true);

    return app(MessageController::class)->delete($userId, is_array($data) ? $data : []);
});

/*
|--------------------------------------------------------------------------
| Invitations
|--------------------------------------------------------------------------
*/

Route::any('/create_invite.php', function () use ($optionalNativeUserId) {
    $serverId = isset($_POST['server_id']) && is_numeric($_POST['server_id']) ? (int) $_POST['server_id'] : null;
    $userId = $optionalNativeUserId();

    return app(InvitationController::class)->create($userId, $serverId);
});

Route::any('/accept_invite.php', function () use ($optionalNativeUserId) {
    $userId = $optionalNativeUserId();
    $code = trim((string) ($_POST['code'] ?? ''));

    return app(InvitationController::class)->accept($userId, $code);
});

Route::any('/invite.php', function (Request $request) {
    return app(InvitationController::class)->resolve($request);
});

/*
|--------------------------------------------------------------------------
| DM
|--------------------------------------------------------------------------
*/

Route::any('/start_dm.php', function (Request $request) use ($requireMethod, $requireAuthUserId, $jsonInput) {
    if ($error = $requireMethod($request, 'POST')) {
        return $error;
    }
    $userId = $requireAuthUserId();
    if ($userId instanceof JsonResponse) {
        return $userId;
    }
    $input = $jsonInput($request);
    if ($input instanceof JsonResponse) {
        return $input;
    }

    return app(DmController::class)->start($userId, $input);
});

Route::any('/get_dm_messages.php', function (Request $request) use ($requireAuthUserId) {
    $userId = $requireAuthUserId();
    if ($userId instanceof JsonResponse) {
        return $userId;
    }

    return app(DmController::class)->messages($userId, (int) $request->query('conversation_id', 0));
});

Route::any('/send_dm.php', function (Request $request) use ($requireMethod, $requireAuthUserId, $jsonInput) {
    if ($error = $requireMethod($request, 'POST')) {
        return $error;
    }
    $userId = $requireAuthUserId();
    if ($userId instanceof JsonResponse) {
        return $userId;
    }
    $data = $jsonInput($request);
    if ($data instanceof JsonResponse) {
        return $data;
    }

    return app(DmController::class)->send($userId, $data);
});

Route::any('/get_dm_notifications.php', function () use ($requireAuthUserId) {
    $userId = $requireAuthUserId();
    if ($userId instanceof JsonResponse) {
        return $userId;
    }

    return app(DmController::class)->notifications($userId);
});

/*
|--------------------------------------------------------------------------
| Moderation
|--------------------------------------------------------------------------
*/

Route::any('/get_my_server_role.php', function (Request $request) use ($requireAuthUserId) {
    $userId = $requireAuthUserId();
    if ($userId instanceof JsonResponse) {
        return $userId;
    }

    $rawServerId = $request->query('server_id');
    $serverId = is_numeric($rawServerId) ? (int) $rawServerId : null;

    return app(RoleModerationController::class)->getMyServerRole($userId, $serverId);
});

Route::any('/get_users_in_server.php', function (Request $request) use ($requireAuthUserId) {
    $userId = $requireAuthUserId();
    if ($userId instanceof JsonResponse) {
        return $userId;
    }

    return app(RoleModerationController::class)->listUsersInServer($userId, (int) $request->query('server_id', 0));
});

Route::any('/set_member_role.php', function (Request $request) use ($requireAuthUserId, $jsonInput) {
    $userId = $requireAuthUserId();
    if ($userId instanceof JsonResponse) {
        return $userId;
    }
    $data = $jsonInput($request);
    if ($data instanceof JsonResponse) {
        return $data;
    }

    return app(RoleModerationController::class)->setMemberRole($userId, $data);
});

Route::any('/kick_member.php', function (Request $request) use ($requireAuthUserId, $jsonInput) {
    $userId = $requireAuthUserId();
    if ($userId instanceof JsonResponse) {
        return $userId;
    }
    $data = $jsonInput($request);
    if ($data instanceof JsonResponse) {
        return $data;
    }

    return app(RoleModerationController::class)->kickMember($userId, $data);
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::any('/get_all_users.php', function () use ($requireAuthUserId) {
    $userId = $requireAuthUserId();
    if ($userId instanceof JsonResponse) {
        return $userId;
    }

    return app(AdminUserController::class)->listUsers($userId);
});

Route::any('/get_user_servers.php', function (Request $request) use ($requireAuthUserId) {
    $userId = $requireAuthUserId();
    if ($userId instanceof JsonResponse) {
        return $userId;
    }

    if (! $request->query->has('user_id') || ! is_numeric($request->query('user_id'))) {
        return new JsonResponse(['success' => false, 'error' => 'Paramètre user_id manquant ou invalide'], 400);
    }

    return app(AdminUserController::class)->listUserServers($userId, (int) $request->query('user_id'));
});

Route::any('/ban_user.php', function (Request $request) {
    return app(BanUserController::class)->handle($request);
});

/*
|--------------------------------------------------------------------------
| Misc
|--------------------------------------------------------------------------
*/

Route::any('/xxx.php', function (Request $request) {
    return app(XxxController::class)->handle($request);
});

Route::any('/health.php', function () {
    return new JsonResponse(['success' => true, 'status' => 'ok']);
});
