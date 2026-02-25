<?php declare(strict_types=1);
namespace hiperesp\server\models;

use hiperesp\server\exceptions\DFException;
use hiperesp\server\vo\HouseItemVO;
use hiperesp\server\vo\HouseItemShopVO;

class HouseItemModel extends Model {

    const COLLECTION = 'houseItem';
    const HOUSE_ITEM_SHOP_ASSOCIATION = 'houseItemShop_houseItem';

    public function getById(int $houseItemId): HouseItemVO {
        $houseItem = $this->storage->select(self::COLLECTION, ['id' => $houseItemId]);
        if(isset($houseItem[0]) && $houseItem = $houseItem[0]) {
            return new HouseItemVO($houseItem);
        }
        throw new DFException(DFException::ITEM_NOT_FOUND);
    }

    public function getByShopAndId(HouseItemShopVO $shop, int $id): HouseItemVO {
        $houseItem = $this->storage->select(self::HOUSE_ITEM_SHOP_ASSOCIATION, [
            'houseItemShopId' => $shop->id,
            'houseItemId' => $id
        ]);
        if(isset($houseItem[0]) && $houseItem = $houseItem[0]) {
            return $this->getById((int)$houseItem['houseItemId']);
        }
        throw new DFException(DFException::ITEM_NOT_FOUND);
    }

    /** @return array<HouseItemVO> */
    public function getByShop(HouseItemShopVO $shop): array {
        $houseItemIds = \array_map(function(array $houseItem): int {
            return (int)$houseItem['houseItemId'];
        }, $this->storage->select(self::HOUSE_ITEM_SHOP_ASSOCIATION, ['houseItemShopId' => $shop->id], null));

        return \array_map(function(array $houseItem): HouseItemVO {
            return new HouseItemVO($houseItem);
        }, $this->storage->select(self::COLLECTION, ['id' => $houseItemIds], null));
    }

}