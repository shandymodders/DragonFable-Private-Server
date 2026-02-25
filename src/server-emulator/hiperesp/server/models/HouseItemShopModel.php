<?php declare(strict_types=1);
namespace hiperesp\server\models;

use hiperesp\server\vo\HouseItemShopVO;

class HouseItemShopModel extends Model {

    const COLLECTION = 'houseItemShop';

    public function getById(int $shopId): ?HouseItemShopVO {
        $shop = $this->storage->select(self::COLLECTION, ['id' => $shopId]);
        if(isset($shop[0]) && $shop = $shop[0]) {
            return new HouseItemShopVO($shop);
        }
        return null;
    }

}