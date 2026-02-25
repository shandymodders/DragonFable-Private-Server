<?php declare(strict_types=1);
namespace hiperesp\server\vo;

use hiperesp\server\interfaces\Purchasable;

class HouseItemVO extends ValueObject implements Purchasable {

    public readonly string $name;
    public readonly string $description;
    public readonly int $visible;
    public readonly int $destroyable;
    public readonly int $equippable;
    public readonly int $randomDrop;
    public readonly int $sellable;
    public readonly int $dragonAmulet;
    public readonly int $enc;
    public readonly int $cost;
    public readonly int $currency;
    public readonly int $maxStackSize;
    public readonly int $rarity;
    public readonly int $level;
    public readonly int $maxLevel;
    public readonly int $category;
    public readonly int $equipSpot;
    public readonly int $itemType;
    public readonly string $type;
    public readonly int $random;
    public readonly int $element;
    public readonly string $swf;
    public readonly string $icon;
    public readonly int $region;
    public readonly int $theme;
    public readonly int $size;
    public readonly int $refId;
    public function getPriceGold(): int {
        return $this->currency === 1 ? $this->cost : 0;
    }

    public function getPriceCoins(): int {
        return $this->currency === 2 ? $this->cost : 0;
    }
}