<?php
namespace hiperesp\server\projection;

use hiperesp\server\vo\HouseShopVO;
use hiperesp\server\vo\CharacterHouseVO;

class HouseShopProjection extends Projection {

    public function loaded(HouseShopVO $shop, array $charHouses = []): \SimpleXMLElement {

        $xml = new \SimpleXMLElement('<houseshop/>');
        $shopEl = $xml->addChild('shop');
        $shopEl->addAttribute('ShopID', (string)$shop->id);
        $shopEl->addAttribute('strCharacterName', $shop->name);

        foreach($charHouses as $charHouse) {
            $house = $charHouse->getHouse();
            $itemEl = $shopEl->addChild('iHouses');

            $itemEl->addAttribute('HouseID', (string)$house->id);
            $itemEl->addAttribute('CharHouseID', (string)$charHouse->id);
            $itemEl->addAttribute('strHouseName', $house->name);
            $itemEl->addAttribute('strHouseDescription', $house->description);
            $itemEl->addAttribute('bitVisible', (string)$house->visible);
            $itemEl->addAttribute('bitDestroyable', (string)$house->destroyable);
            $itemEl->addAttribute('bitEquippable', (string)$house->equippable);
            $itemEl->addAttribute('bitRandomDrop', (string)$house->randomDrop);
            $itemEl->addAttribute('bitSellable', (string)$house->sellable);
            $itemEl->addAttribute('bitDragonAmulet', (string)$house->dragonAmulet);
            $itemEl->addAttribute('bitEnc', (string)$house->enc);
            $itemEl->addAttribute('intCost', (string)$house->cost);
            $itemEl->addAttribute('intCurrency', (string)$house->currency);
            $itemEl->addAttribute('intRarity', (string)$house->rarity);
            $itemEl->addAttribute('intLevel', (string)$house->level);
            $itemEl->addAttribute('intCategory', (string)$house->category);
            $itemEl->addAttribute('intEquipSpot', (string)$house->equipSpot);
            $itemEl->addAttribute('intType', (string)$house->type);
            $itemEl->addAttribute('bitRandom', (string)$house->random);
            $itemEl->addAttribute('intElement', (string)$house->element);
            $itemEl->addAttribute('strType', $house->type);
            $itemEl->addAttribute('strIcon', $house->icon);
            $itemEl->addAttribute('strDesignInfo', $house->designInfo);
            $itemEl->addAttribute('strFileName', $house->swf);
            $itemEl->addAttribute('intRegion', (string)$house->region);
            $itemEl->addAttribute('intTheme', (string)$house->theme);
            $itemEl->addAttribute('intSize', (string)$house->size);
            $itemEl->addAttribute('intBaseHP', (string)$house->baseHP);
            $itemEl->addAttribute('intStorageSize', (string)$house->storageSize);
            $itemEl->addAttribute('intMaxGuards', (string)$house->maxGuards);
            $itemEl->addAttribute('intMaxRooms', (string)$house->maxRooms);
            $itemEl->addAttribute('bitEquipped', (string)($charHouse->equipped ? 1 : 0));
            $itemEl->addAttribute('intMaxExtItems', (string)$house->maxExtItems);
            $itemEl->addAttribute('intHoursOwned', (string)$charHouse->hoursOwned);
        }

        foreach($shop->getHouses() as $item) {
            $itemEl = $shopEl->addChild('sHouses');

            $itemEl->addAttribute('HouseID', $item->id);
            $itemEl->addAttribute('strHouseName', $item->name);
            $itemEl->addAttribute('strHouseDescription', $item->description);
            $itemEl->addAttribute('bitVisible', $item->visible);
            $itemEl->addAttribute('bitDestroyable', $item->destroyable);
            $itemEl->addAttribute('bitEquippable', $item->equippable);
            $itemEl->addAttribute('bitRandomDrop', $item->randomDrop);
            $itemEl->addAttribute('bitSellable', $item->sellable);
            $itemEl->addAttribute('bitDragonAmulet', $item->dragonAmulet);
            $itemEl->addAttribute('bitEnc', $item->enc);
            $itemEl->addAttribute('intCost', $item->cost);
            $itemEl->addAttribute('intCurrency', $item->currency);
            $itemEl->addAttribute('intRarity', $item->rarity);
            $itemEl->addAttribute('intLevel', $item->level);
            $itemEl->addAttribute('intCategory', $item->category);
            $itemEl->addAttribute('intEquipSpot', $item->equipSpot);
            $itemEl->addAttribute('intType', $item->type);
            $itemEl->addAttribute('bitRandom', $item->random);
            $itemEl->addAttribute('intElement', $item->element);
            $itemEl->addAttribute('strType', $item->type);
            $itemEl->addAttribute('strIcon', $item->icon);
            $itemEl->addAttribute('strDesignInfo', $item->designInfo);
            $itemEl->addAttribute('strFileName', $item->swf);
            $itemEl->addAttribute('intRegion', $item->region);
            $itemEl->addAttribute('intTheme', $item->theme);
            $itemEl->addAttribute('intSize', $item->size);
            $itemEl->addAttribute('intBaseHP', $item->baseHP);
            $itemEl->addAttribute('intStorageSize', $item->storageSize);
            $itemEl->addAttribute('intMaxGuards', $item->maxGuards);
            $itemEl->addAttribute('intMaxRooms', $item->maxRooms);
            $itemEl->addAttribute('intMaxExtItems', $item->maxExtItems);

        }

        return $xml;
    }

    public function bought(CharacterHouseVO $charHouse): \SimpleXMLElement {
        $xml = new \SimpleXMLElement('<buyMech xmlns:sql="urn:schemas-microsoft-com:xml-sql"/>');
        $xml->addChild('CharHouseItemID', (string)$charHouse->id);
        return $xml;
    }

    public function sold(): \SimpleXMLElement {
        return new \SimpleXMLElement('<sellMech xmlns:sql="urn:schemas-microsoft-com:xml-sql"/>');
    }

    public function equipped(): \SimpleXMLElement {
        $xml = new \SimpleXMLElement('<sellHouse xmlns:sql="urn:schemas-microsoft-com:xml-sql"/>');
        $xml->addChild('status', 'SUCCESS');
        return $xml;
    }
}