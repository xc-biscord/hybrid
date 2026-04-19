<?php

declare(strict_types=1);

namespace Tests\Contract\Support;

final class SessionHelper
{
    /**
     * Crée une session PHP native contenant user_id puis l'attache au client HTTP.
     */
    public static function actingAs(BiscordHttpClient $client, int $userId): string
    {
        $sessionId = 'contract_' . bin2hex(random_bytes(8));
        $payload = sprintf('user_id|i:%d;', $userId);

        $savePath = (string) ini_get('session.save_path');
        if ($savePath === '') {
            $savePath = sys_get_temp_dir();
        }

        $sessionFile = rtrim($savePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'sess_' . $sessionId;

        if (file_put_contents($sessionFile, $payload) === false) {
            throw new \RuntimeException('Unable to write PHP session file: ' . $sessionFile);
        }

        $client->setPhpSessionId($sessionId);

        return $sessionId;
    }
}
