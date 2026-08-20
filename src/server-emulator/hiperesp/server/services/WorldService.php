<?php declare(strict_types=1);
namespace hiperesp\server\services;

use hiperesp\server\attributes\Inject;
use hiperesp\server\exceptions\DFException;
use hiperesp\server\models\CharacterModel;
use hiperesp\server\models\UserModel;
use hiperesp\server\vo\CharacterVO;

class WorldService extends Service {

    private const PLAYER_TTL_SECONDS = 5.0;
    private const UPDATE_INTERVAL_US = 50_000;
    private const MAX_MAP_LENGTH = 512;

    #[Inject] private UserModel $userModel;
    #[Inject] private CharacterModel $characterModel;

    public function update(string $token, array $state): void {
        if($token === '') {
            throw new DFException(DFException::INVALID_SESSION);
        }

        $user = $this->userModel->getBySessionToken($token);
        $map = $this->normalizeMap((string)($state['map'] ?? ''));
        if($map === '') {
            return;
        }

        $charId = (int)($state['charId'] ?? 0);
        if($charId <= 0) {
            return;
        }

        $x = $this->finiteNumber($state['x'] ?? null);
        $y = $this->finiteNumber($state['y'] ?? null);
        if($x === null || $y === null) {
            return;
        }

        $scaleX = $this->finiteNumber($state['scaleX'] ?? 100.0) ?? 100.0;
        $scaleY = $this->finiteNumber($state['scaleY'] ?? 100.0) ?? 100.0;
        $direction = $this->normalizeText((string)($state['dir'] ?? ''), 16);
        $frame = $this->finiteNumber($state['frame'] ?? 1.0) ?? 1.0;
        $animation = $this->normalizeText((string)($state['animation'] ?? ''), 96);
        $animSerial = \max(0, \min(2_147_483_647, (int)($state['animSerial'] ?? 0)));
        $equipment = $this->normalizeEquipment($state['equipment'] ?? null);
        $runtimeClassId = (int)($state['classId'] ?? 0);
        $runtimeClassFile = $this->normalizeClassFile((string)($state['classFile'] ?? ''));
        $moving = (bool)($state['moving'] ?? false);
        $characterName = $this->normalizeText((string)($state['name'] ?? ''), 64);

        $char = $this->characterModel->getById($charId);
        if($char->userId !== $user->id) {
            throw new DFException(DFException::CHARACTER_NOT_FOUND);
        }

        $this->mutateState(function(array &$players) use($user, $charId, $map, $x, $y, $scaleX, $scaleY, $direction, $frame, $animation, $animSerial, $equipment, $runtimeClassId, $runtimeClassFile, $moving, $characterName): void {
            $players[(string)$user->id] = [
                'id' => $user->id,
                'charId' => $charId,
                'classId' => $runtimeClassId,
                'classFile' => $runtimeClassFile,
                'moving' => $moving,
                'username' => $user->username,
                'name' => $characterName !== '' ? $characterName : $user->username,
                'map' => $map,
                'x' => $x,
                'y' => $y,
                'scaleX' => $scaleX,
                'scaleY' => $scaleY,
                'dir' => $direction,
                'frame' => $frame,
                'animation' => $animation,
                'animSerial' => $animSerial,
                'equipment' => $equipment,
                'updatedAt' => \microtime(true),
            ];
        });
    }

    public function leave(string $token): void {
        if($token === '') return;
        $user = $this->userModel->getBySessionToken($token);
        $this->mutateState(function(array &$players) use($user): void {
            unset($players[(string)$user->id]);
        });
    }

    public function getPlayers(string $token, string $map): array {
        if($token === '') {
            throw new DFException(DFException::INVALID_SESSION);
        }

        $user = $this->userModel->getBySessionToken($token);
        $map = $this->normalizeMap($map);
        $now = \microtime(true);
        $result = [];

        $this->mutateState(function(array &$players) use($user, $map, $now, &$result): void {
            foreach($players as $id => $player) {
                if(($now - (float)($player['updatedAt'] ?? 0)) > self::PLAYER_TTL_SECONDS) {
                    unset($players[$id]);
                    continue;
                }

                if((int)$id === $user->id) continue;
                if(($player['map'] ?? '') !== $map) continue;

                unset($player['updatedAt']);
                $result[] = $player;
            }
        });

        return $result;
    }

    public function getAppearance(string $token, int $charId): CharacterVO {
        if($token === '') {
            throw new DFException(DFException::INVALID_SESSION);
        }
        if($charId <= 0) {
            throw new DFException(DFException::CHARACTER_NOT_FOUND);
        }

        // A valid logged-in session is required. Character appearance itself is
        // intentionally readable by character id, matching the existing PvP
        // character loader behavior used by DragonFable.
        $this->userModel->getBySessionToken($token);
        return $this->characterModel->getById($charId);
    }

    public function eventSource(string $token, string $map): callable {
        $lastJson = null;
        $first = true;

        return function() use($token, $map, &$lastJson, &$first): array {
            if(!$first) {
                \usleep(self::UPDATE_INTERVAL_US);
            }
            $first = false;

            $json = \json_encode($this->getPlayers($token, $map), \JSON_UNESCAPED_SLASHES);
            if($json === $lastJson) {
                return [ 'event' => 'update' ];
            }

            $lastJson = $json;
            return [
                'event' => 'message',
                'data' => $json,
            ];
        };
    }

    private function normalizeMap(string $map): string {
        $map = \trim($map);
        if($map === '') return '';
        if(\strlen($map) > self::MAX_MAP_LENGTH) {
            $map = \substr($map, 0, self::MAX_MAP_LENGTH);
        }
        return $map;
    }

    private function normalizeText(string $value, int $maxLength): string {
        $value = \trim($value);
        if(\strlen($value) > $maxLength) {
            $value = \substr($value, 0, $maxLength);
        }
        return $value;
    }

    private function normalizeClassFile(string $value): string {
        $value = \str_replace('\\', '/', \trim($value));
        $value = \basename($value);
        if($value === '' || \strlen($value) > 160) return '';
        if(!\preg_match('/^[A-Za-z0-9 _.-]+\.swf$/i', $value)) return '';
        return $value;
    }

    private function normalizeEquipment(mixed $value): array {
        $equipment = \is_array($value) ? $value : [];
        return [
            'weapon' => $this->normalizeEquipmentSlot($equipment['weapon'] ?? null),
            'back' => $this->normalizeEquipmentSlot($equipment['back'] ?? null),
            'head' => $this->normalizeEquipmentSlot($equipment['head'] ?? null),
        ];
    }

    private function normalizeEquipmentSlot(mixed $value): array {
        $slot = \is_array($value) ? $value : [];
        $file = \str_replace('\\', '/', \trim((string)($slot['file'] ?? '')));

        // Runtime equipment is allowed to reference only a relative SWF asset.
        // This prevents a forged multiplayer packet from loading another origin
        // or traversing outside the configured gamefiles directory on viewers.
        if(
            $file === '' ||
            \strlen($file) > 255 ||
            \str_starts_with($file, '/') ||
            \str_contains($file, '..') ||
            !\preg_match('/^[^\x00-\x1F\x7F?#:]+\.swf$/i', $file)
        ) {
            $file = '';
        }

        return [
            'file' => $file,
            'itemType' => $this->normalizeText((string)($slot['itemType'] ?? ''), 64),
            'type' => $this->normalizeText((string)($slot['type'] ?? ''), 64),
            'visible' => $file !== '' && (
                ($slot['visible'] ?? false) === true ||
                (int)($slot['visible'] ?? 0) === 1
            ),
        ];
    }

    private function finiteNumber(mixed $value): ?float {
        if(!\is_numeric($value)) return null;
        $number = (float)$value;
        if(!\is_finite($number)) return null;
        if(\abs($number) > 1_000_000) return null;
        return $number;
    }

    private function getStateFile(): string {
        return \sys_get_temp_dir().\DIRECTORY_SEPARATOR.'df-world-state.json';
    }

    private function mutateState(callable $callback): void {
        $file = $this->getStateFile();
        $handle = \fopen($file, 'c+');
        if($handle === false) {
            throw new \RuntimeException('Unable to open world state file');
        }

        try {
            if(!\flock($handle, \LOCK_EX)) {
                throw new \RuntimeException('Unable to lock world state file');
            }

            \rewind($handle);
            $raw = \stream_get_contents($handle);
            $players = $raw ? \json_decode($raw, true) : [];
            if(!\is_array($players)) $players = [];

            $callback($players);

            \ftruncate($handle, 0);
            \rewind($handle);
            \fwrite($handle, \json_encode($players, \JSON_UNESCAPED_SLASHES));
            \fflush($handle);
            \flock($handle, \LOCK_UN);
        } finally {
            \fclose($handle);
        }
    }
}
