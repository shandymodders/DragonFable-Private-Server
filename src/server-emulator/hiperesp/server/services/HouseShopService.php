<?php declare(strict_types=1);
namespace hiperesp\server\services;

use hiperesp\server\attributes\Inject;
use hiperesp\server\exceptions\DFException;
use hiperesp\server\models\CharacterHouseModel;
use hiperesp\server\models\CharacterModel;
use hiperesp\server\models\HouseModel;
use hiperesp\server\models\HouseShopModel;
use hiperesp\server\models\CharHouseItemModel;
use hiperesp\server\models\LogsModel;
use hiperesp\server\vo\CharacterHouseVO;
use hiperesp\server\vo\CharacterVO;
use hiperesp\server\vo\HouseShopVO;

class HouseShopService extends Service {

    #[Inject] private HouseShopModel $houseShopModel;
    #[Inject] private HouseModel $houseModel;
    #[Inject] private CharacterHouseModel $characterHouseModel;
    #[Inject] private CharacterModel $characterModel;
    #[Inject] private CharHouseItemModel $charHouseItemModel;
    #[Inject] private LogsModel $logsModel;

    public function getShop(int $shopId): HouseShopVO {
        return $this->houseShopModel->getById($shopId);
    }

    public function getOwnedHouses(CharacterVO $char): array {
        return $this->characterHouseModel->getByChar($char);
    }

    public function buy(CharacterVO $char, int $shopId, int $houseId): CharacterHouseVO {
        try {
            $shop = $this->houseShopModel->getById($shopId);
            $house = $this->houseModel->getByShopAndId($shop, $houseId);
        } catch (\Exception $e) {
            throw $this->logsModel->register(LogsModel::SEVERITY_BLOCKED, 'buyHouse', 'Invalid shopId or houseId', $char, $char, [
                'shopId'  => $shopId,
                'houseId' => $houseId,
            ])->asException(DFException::INVALID_REFERENCE);
        }

        $owned = $this->characterHouseModel->countByChar($char);
        if ($owned >= $char->maxHouseSlots) {
            throw $this->logsModel->register(LogsModel::SEVERITY_BLOCKED, 'buyHouse', 'House slots full', $char, $char, [
                'owned'      => $owned,
                'houseSlots' => $char->houseSlots,
            ])->asException(DFException::CANNOT_BUY_ITEM);
        }

        if (!$char->canBuy($house)) {
            throw $this->logsModel->register(LogsModel::SEVERITY_BLOCKED, 'buyHouse', 'Cannot buy house', $char, $char, [
                'gold'  => $char->gold,
                'coins' => $char->coins,
            ])->asException(DFException::CANNOT_BUY_ITEM);
        }

        $charHouse = $this->characterHouseModel->addHouseToChar($char, $house);
        $this->characterModel->charge($char, $house);

        $this->logsModel->register(LogsModel::SEVERITY_ALLOWED, 'buyHouse', 'House bought', $char, $charHouse, []);
        return $charHouse;
    }

    public function sell(CharacterVO $char, int $charHouseId): void {
        $charHouse = $this->characterHouseModel->getByCharAndId($char, $charHouseId);

        $this->characterModel->refundHouse($char, $charHouse, returnPercent: 10);
        $this->characterHouseModel->destroy($charHouse);

        $this->logsModel->register(LogsModel::SEVERITY_ALLOWED, 'sellHouse', 'House sold', $char, $charHouse, []);
    }

    public function equip(CharacterVO $char, int $charHouseId): void {
        $charHouse = $this->characterHouseModel->getByCharAndId($char, $charHouseId);

        $this->charHouseItemModel->unequipAll($char);

        $this->characterHouseModel->equip($char, $charHouse);

        $this->logsModel->register(LogsModel::SEVERITY_ALLOWED, 'equipHouse', 'House equipped', $char, $charHouse, []);
    }
}