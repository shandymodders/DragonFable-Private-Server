<?php declare(strict_types=1);
namespace hiperesp\server\vo;

use hiperesp\server\attributes\Inject;
use hiperesp\server\models\CharacterModel;
use hiperesp\server\models\HouseItemModel;

class CharHouseItemVO extends ValueObject {

    #[Inject] private HouseItemModel $houseItemModel;
    #[Inject] private CharacterModel $characterModel;

    public readonly int $charId;

    public readonly string $createdAt;
    public readonly string $updatedAt;

    public readonly int $houseItemId;
    public readonly int $equipSlotPos;
    public readonly int $count;

    public int $hoursOwned {
        get {
            $todaySeconds = \strtotime(\date('c'));
            $ownedAtSeconds = \strtotime($this->createdAt);

            $secondsOwned = $todaySeconds - $ownedAtSeconds;
            $hoursOwned = (int)\floor($secondsOwned / 3600);

            return $hoursOwned;
        }
    }

    public function getHouseItem(): HouseItemVO {
        return $this->houseItemModel->getById($this->houseItemId);
    }

    public function getChar(): CharacterVO {
        return $this->characterModel->getByCharHouseItem($this);
    }

}