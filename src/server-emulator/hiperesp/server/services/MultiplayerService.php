<?php declare(strict_types=1);
namespace hiperesp\server\services;

use hiperesp\server\attributes\Inject;
use hiperesp\server\exceptions\DFException;
use hiperesp\server\models\UserModel;
use hiperesp\server\vo\CharacterVO;
use hiperesp\server\vo\UserVO;

/**
 * Lightweight realtime presence/position service.
 *
 * This intentionally stores only ephemeral data in the system temp directory.
 * Character/account ownership is validated before every position update.
 */
class MultiplayerService extends Service {

    private const PLAYER_TTL_SECONDS = 8.0;
    private const STREAM_INTERVAL_US = 200_000; // 5 updates/second
    private const MAX_MAP_KEY_LENGTH = 512;

    #[Inject] private UserModel $userModel;

    public function update(CharacterVO $char, string $mapKey, float $x, float $y, float $scaleX = 100.0, float $scaleY = 100.0): void {
        $mapKey = \trim($mapKey);
        if(\strlen($mapKey) > self::MAX_MAP_KEY_LENGTH) {
            $mapKey = \substr($mapKey, 0, self::MAX_MAP_KEY_LENGTH);
        }

        // Keep broken/forged client values from creating absurd coordinates.
        $x = \max(-20_000.0, \min(20_000.0, $x));
        $y = \max(-20_000.0, \min(20_000.0, $y));
        $scaleX = \max(-1_000.0, \min(1_000.0, $scaleX));
        $scaleY = \max(-1_000.0, \min(1_000.0, $scaleY));

        $user = $this->userModel->getByChar($char);
        $this->withStateLock(true, function(array &$state) use($user, $char, $mapKey, $x, $y, $scaleX, $scaleY): void {
            $this->removeExpired($state);

            // One active character per account/browser session is sufficient here.
            $state['players'][(string)$user->id] = [
                'userId' => $user->id,
                'charId' => $char->id,
                'name' => $char->name,
                'mapKey' => $mapKey,
                'x' => $x,
                'y' => $y,
                'scaleX' => $scaleX,
                'scaleY' => $scaleY,
                'updatedAt' => \microtime(true),
            ];
        });
    }

    public function leave(string $userToken): void {
        $user = $this->userModel->getBySessionToken($userToken);
        $this->withStateLock(true, function(array &$state) use($user): void {
            unset($state['players'][(string)$user->id]);
            $this->removeExpired($state);
        });
    }

    public function eventSource(string $userToken): callable {
        $viewer = $this->userModel->getBySessionToken($userToken);
        $firstUpdate = true;

        return function() use($viewer, &$firstUpdate): array {
            if($firstUpdate) {
                $firstUpdate = false;
            } else {
                \usleep(self::STREAM_INTERVAL_US);
            }

            $players = $this->getVisiblePlayers($viewer);

            return [
                'event' => 'message',
                'data' => \json_encode([
                    'players' => $players,
                    'serverTime' => \microtime(true),
                ], \JSON_UNESCAPED_SLASHES),
            ];
        };
    }

    private function getVisiblePlayers(UserVO $viewer): array {
        $state = $this->readState();
        $now = \microtime(true);
        $players = [];

        foreach($state['players'] as $player) {
            if(($now - (float)($player['updatedAt'] ?? 0)) > self::PLAYER_TTL_SECONDS) {
                continue;
            }
            if((int)($player['userId'] ?? 0) === $viewer->id) {
                continue;
            }

            // Do not expose account IDs or session data to other clients.
            $players[] = [
                'charId' => (int)$player['charId'],
                'name' => (string)$player['name'],
                'mapKey' => (string)$player['mapKey'],
                'x' => (float)$player['x'],
                'y' => (float)$player['y'],
                'scaleX' => (float)$player['scaleX'],
                'scaleY' => (float)$player['scaleY'],
                'updatedAt' => (float)$player['updatedAt'],
            ];
        }

        return $players;
    }

    private function removeExpired(array &$state): void {
        $now = \microtime(true);
        foreach($state['players'] as $key => $player) {
            if(($now - (float)($player['updatedAt'] ?? 0)) > self::PLAYER_TTL_SECONDS) {
                unset($state['players'][$key]);
            }
        }
    }

    private function readState(): array {
        $file = $this->getStateFile();
        $handle = \fopen($file, 'c+');
        if($handle === false) {
            throw new DFException('Could not open multiplayer state file');
        }

        try {
            \flock($handle, \LOCK_SH);
            \rewind($handle);
            $raw = \stream_get_contents($handle);
            $state = $this->decodeState($raw === false ? '' : $raw);
            \flock($handle, \LOCK_UN);
            return $state;
        } finally {
            \fclose($handle);
        }
    }

    /**
     * @param callable(array&):void $callback
     */
    private function withStateLock(bool $write, callable $callback): void {
        $file = $this->getStateFile();
        $handle = \fopen($file, 'c+');
        if($handle === false) {
            throw new DFException('Could not open multiplayer state file');
        }

        try {
            \flock($handle, $write ? \LOCK_EX : \LOCK_SH);
            \rewind($handle);
            $raw = \stream_get_contents($handle);
            $state = $this->decodeState($raw === false ? '' : $raw);

            $callback($state);

            if($write) {
                \rewind($handle);
                \ftruncate($handle, 0);
                \fwrite($handle, \json_encode($state, \JSON_UNESCAPED_SLASHES));
                \fflush($handle);
            }
            \flock($handle, \LOCK_UN);
        } finally {
            \fclose($handle);
        }
    }

    private function decodeState(string $raw): array {
        $decoded = $raw !== '' ? \json_decode($raw, true) : null;
        if(!\is_array($decoded)) {
            $decoded = [];
        }
        if(!isset($decoded['players']) || !\is_array($decoded['players'])) {
            $decoded['players'] = [];
        }
        return $decoded;
    }

    private function getStateFile(): string {
        $file = \sys_get_temp_dir().\DIRECTORY_SEPARATOR.'df-multiplayer.json';
        if(!\file_exists($file)) {
            @\file_put_contents($file, \json_encode(['players' => []]), \LOCK_EX);
        }
        return $file;
    }
}
