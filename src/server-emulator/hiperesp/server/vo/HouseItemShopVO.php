<?php declare(strict_types=1);
namespace hiperesp\server\vo;

use hiperesp\server\attributes\Inject;
use hiperesp\server\models\HouseItemModel;

class HouseItemShopVO extends ValueObject {

    #[Inject] private HouseItemModel $houseItemModel;

    public readonly string $name;

    /** @return array<HouseItemVO> */
    public function getItems(): array {
        return $this->houseItemModel->getByShop($this);
    }

}