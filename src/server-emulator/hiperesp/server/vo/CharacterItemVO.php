<?php declare(strict_types=1);
namespace hiperesp\server\vo;

use hiperesp\server\attributes\Inject;
use hiperesp\server\models\CharacterModel;
use hiperesp\server\models\ItemModel;

class CharacterItemVO extends ValueObject {

    #[Inject] private ItemModel $itemModel;
    #[Inject] private CharacterModel $characterModel;

    public readonly int $charId;

    public readonly string $createdAt;
    public readonly string $updatedAt;

    public readonly int $itemId;

    public readonly bool $equipped;
    public readonly int $count;
    public readonly int $level;
    public readonly int $experience;
    public readonly bool $banked;

    public int $experienceToLevel {
        get {
            return self::experienceRequiredForLevel($this->level);
        }
    }

    public int $hoursOwned {
        get {
            $todaySeconds = \strtotime(\date('c'));
            $ownedAtSeconds = \strtotime($this->createdAt);

            $secondsOwned = $todaySeconds - $ownedAtSeconds;
            $hoursOwned = (int)\floor($secondsOwned / 3600);

            return $hoursOwned;
        }
    }

    public function getItem(): ItemVO {
        return $this->itemModel->getByCharItem($this);
    }

    public function getChar(): CharacterVO {
        return $this->characterModel->getByCharItem($this);
    }

    public static function experienceRequiredForLevel(int $level): int {
        $level = \max(1, $level);
        return (int)((62 + 3 * $level + 55 * $level * $level) / 2);
    }

}
