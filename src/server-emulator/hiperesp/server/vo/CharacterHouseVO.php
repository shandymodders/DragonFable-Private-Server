<?php declare(strict_types=1);
namespace hiperesp\server\vo;

use hiperesp\server\attributes\Inject;
use hiperesp\server\models\HouseModel;

class CharacterHouseVO extends ValueObject {

    #[Inject] private HouseModel $houseModel;

    public readonly int $charId;
    public readonly int $houseId;
    public readonly string $createdAt;
    public readonly string $updatedAt;
    public readonly bool $equipped;

    public int $hoursOwned {
        get {
            $todaySeconds = \strtotime(\date('c'));
            $ownedAtSeconds = \strtotime($this->createdAt);
            $secondsOwned = $todaySeconds - $ownedAtSeconds;
            return (int)\floor($secondsOwned / 3600);
        }
    }

    public function getHouse(): HouseVO {
        return $this->houseModel->getById($this->houseId);
    }

}