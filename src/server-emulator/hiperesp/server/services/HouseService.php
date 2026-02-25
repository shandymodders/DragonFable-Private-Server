<?php declare(strict_types=1);
namespace hiperesp\server\services;

use hiperesp\server\attributes\Inject;
use hiperesp\server\exceptions\DFException;
use hiperesp\server\models\CharacterHouseModel;
use hiperesp\server\vo\CharacterHouseVO;
use hiperesp\server\vo\CharacterVO;

class HouseService extends Service {

    #[Inject] private CharacterHouseModel $characterHouseModel;

    public function getEquipped(CharacterVO $char): CharacterHouseVO {
        $charHouse = $this->characterHouseModel->getEquippedByChar($char);
        if ($charHouse === null) {
            throw new DFException(DFException::INVALID_REFERENCE);
        }
        return $charHouse;
    }

}