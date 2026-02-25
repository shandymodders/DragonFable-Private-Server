<?php declare(strict_types=1);
namespace hiperesp\server\vo;

use hiperesp\server\enums\Currency;
use hiperesp\server\interfaces\Purchasable;

class HouseVO extends ValueObject implements Purchasable {

    public readonly string $name;
    public readonly string $description;
    public readonly int $visible;
    public readonly bool $destroyable;
    public readonly bool $equippable;
    public readonly bool $randomDrop;
    public readonly bool $sellable;
    public readonly bool $dragonAmulet;
    public readonly bool $enc;
    public readonly int $cost;
    public readonly int $currency;
    public readonly bool $rarity;
    public readonly int $level;
    public readonly int $category;
    public readonly int $equipSpot;
    public readonly string $type;
    public readonly int $random;
    public readonly int $element;
    public readonly string $icon;
    public readonly string $designInfo;
    public readonly string $swf;
    public readonly int $region;
    public readonly int $theme;
    public readonly int $size;
    public readonly int $baseHP;
    public readonly int $storageSize;
    public readonly int $maxGuards;
    public readonly int $maxRooms;
    public readonly int $maxExtItems;

    public function getPriceGold(): int {
        return $this->currency === Currency::GOLD ? $this->cost : 0;
    }

    public function getPriceCoins(): int {
        return $this->currency === Currency::DRAGON_COINS ? $this->cost : 0;
    }

}