<?php declare(strict_types=1);
namespace hiperesp\server\services;

use hiperesp\server\attributes\Inject;
use hiperesp\server\exceptions\DFException;
use hiperesp\server\models\CharacterModel;
use hiperesp\server\models\CharHouseItemModel;
use hiperesp\server\models\HouseItemModel;
use hiperesp\server\models\HouseItemShopModel;
use hiperesp\server\models\LogsModel;
use hiperesp\server\vo\CharacterVO;
use hiperesp\server\vo\CharHouseItemVO;
use hiperesp\server\vo\HouseItemShopVO;
use hiperesp\server\vo\SettingsVO;

class HouseItemShopService extends Service {

    #[Inject] private HouseItemShopModel $houseItemShopModel;
    #[Inject] private HouseItemModel $houseItemModel;
    #[Inject] private CharHouseItemModel $charHouseItemModel;
    #[Inject] private CharacterModel $characterModel;
    #[Inject] private LogsModel $logsModel;
    #[Inject] private SettingsVO $settings;

    public function getShop(int $shopId): ?HouseItemShopVO {
        return $this->houseItemShopModel->getById($shopId);
    }

    public function buy(CharacterVO $char, int $shopId, int $houseItemId): CharHouseItemVO {
        try {
            $shop = $this->houseItemShopModel->getById($shopId);
            $houseItem = $this->houseItemModel->getByShopAndId($shop, $houseItemId);
        } catch(\Exception $e) {
            throw $this->logsModel->register(LogsModel::SEVERITY_BLOCKED, 'buyHouseItem', 'Invalid shopId or houseItemId', $char, $char, [
                'shopId'      => $shopId,
                'houseItemId' => $houseItemId,
            ])->asException(DFException::INVALID_REFERENCE);
        }

        if(!$char->canBuy($houseItem)) {
            throw $this->logsModel->register(LogsModel::SEVERITY_BLOCKED, 'buyHouseItem', 'Cannot buy houseItem', $char, $houseItem, [])->asException(DFException::CANNOT_BUY_ITEM);
        }

        $charHouseItem = $this->charHouseItemModel->addHouseItemToChar($char, $houseItem);
        $this->characterModel->charge($char, $houseItem);

        $this->logsModel->register(LogsModel::SEVERITY_ALLOWED, 'buyHouseItem', 'HouseItem bought', $char, $charHouseItem, []);
        return $charHouseItem;
    }

    public function sell(CharacterVO $char, int $charHouseItemId, int $returnPercent): void {
        $charHouseItem = $this->charHouseItemModel->getByCharAndId($char, $charHouseItemId);

        if($this->settings->revalidateClientValues) {
            $houseItem = $charHouseItem->getHouseItem();
            if($houseItem->currency === 1) {
                $newReturnPercent = $charHouseItem->hoursOwned >= 24 ? 25 : 90;
            } else {
                $newReturnPercent = 10;
            }
            if($returnPercent != $newReturnPercent) {
                if($this->settings->banInvalidClientValues) {
                    throw $this->logsModel->register(LogsModel::SEVERITY_BLOCKED, 'sellHouseItem', "Invalid returnPercent. Should be {$newReturnPercent}.", $char, $charHouseItem, [
                        'returnPercent' => $returnPercent,
                    ])->asException(DFException::INVALID_REFERENCE);
                }
            }
            $returnPercent = $newReturnPercent;
        }

        $this->characterModel->refundHouseItem($char, $charHouseItem,  // ← 수정
            returnPercent: $returnPercent
        );
        $this->charHouseItemModel->destroy($charHouseItem);

        $this->logsModel->register(LogsModel::SEVERITY_ALLOWED, 'sellHouseItem', 'HouseItem sold', $char, $charHouseItem, [
            'returnPercent' => $returnPercent,
        ]);
    }

    public function equip(CharacterVO $char, int $charHouseItemId, int $slot): void {
        $charHouseItem = $this->charHouseItemModel->getByCharAndId($char, $charHouseItemId);
        $this->charHouseItemModel->setEquipSlotPos($charHouseItem, $slot);

        $this->logsModel->register(LogsModel::SEVERITY_ALLOWED, 'equipHouseItem', 'HouseItem equipped', $char, $charHouseItem, [
            'slot' => $slot,
        ]);
    }

    public function unequip(CharacterVO $char, int $slot): void {
        $charHouseItem = $this->charHouseItemModel->getByCharAndSlot($char, $slot);
        if($charHouseItem === null) {
            return;
        }

        $this->charHouseItemModel->setEquipSlotPos($charHouseItem, 0);

        $this->logsModel->register(LogsModel::SEVERITY_ALLOWED, 'unequipHouseItem', 'HouseItem unequipped', $char, $charHouseItem, [
            'slot' => $slot,
        ]);
    }

}