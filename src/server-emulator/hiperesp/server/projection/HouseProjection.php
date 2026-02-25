<?php declare(strict_types=1);
namespace hiperesp\server\projection;

use hiperesp\server\vo\CharacterHouseVO;
use hiperesp\server\vo\CharacterVO;

class HouseProjection extends Projection {

    public function loaded(CharacterVO $char, CharacterHouseVO $charHouse): \SimpleXMLElement {
        $xml = new \SimpleXMLElement('<LoadTown xmlns:sql="urn:schemas-microsoft-com:xml-sql"/>');

        $house = $charHouse->getHouse();

        $detailEl = $xml->addChild('vHouseDetails');
        $detailEl->addAttribute('tag', '1');
        $detailEl->addAttribute('idCore_CharHouseAdj', (string)$charHouse->id);
        $detailEl->addAttribute('idCore_Characters', (string)$char->id);
        $detailEl->addAttribute('idCore_Houses', (string)$house->id);
        $detailEl->addAttribute('bitEquipped', $charHouse->equipped ? '1' : '0');
        $detailEl->addAttribute('Expr1', (string)$house->id);
        $detailEl->addAttribute('strHouseName', $house->name);
        $detailEl->addAttribute('strHouseDescription', $house->description);
        $detailEl->addAttribute('bitVisible', (string)$house->visible);
        $detailEl->addAttribute('bitDestroyable', $house->destroyable ? '1' : '0');
        $detailEl->addAttribute('bitEquippable', $house->equippable ? '1' : '0');
        $detailEl->addAttribute('bitRandomDrop', $house->randomDrop ? '1' : '0');
        $detailEl->addAttribute('bitSellable', $house->sellable ? '1' : '0');
        $detailEl->addAttribute('bitDragonAmulet', $house->dragonAmulet ? '1' : '0');
        $detailEl->addAttribute('bitEnc', $house->enc ? '1' : '0');
        $detailEl->addAttribute('intCost', (string)$house->cost);
        $detailEl->addAttribute('intCurrency', (string)$house->currency);
        $detailEl->addAttribute('intRarity', $house->rarity ? '1' : '0');
        $detailEl->addAttribute('intLevel', (string)$house->level);
        $detailEl->addAttribute('intCategory', (string)$house->category);
        $detailEl->addAttribute('intEquipSpot', (string)$house->equipSpot);
        $detailEl->addAttribute('intType', (string)$house->category);
        $detailEl->addAttribute('bitRandom', (string)$house->random);
        $detailEl->addAttribute('intElement', (string)$house->element);
        $detailEl->addAttribute('strType', $house->type);
        $detailEl->addAttribute('strIcon', $house->icon);
        $detailEl->addAttribute('strDesignInfo', $house->designInfo);
        $detailEl->addAttribute('strFileName', $house->swf);
        $detailEl->addAttribute('intRegion', (string)$house->region);
        $detailEl->addAttribute('intTheme', (string)$house->theme);
        $detailEl->addAttribute('intSize', (string)$house->size);
        $detailEl->addAttribute('intBaseHP', (string)$house->baseHP);
        $detailEl->addAttribute('intStorageSize', (string)$house->storageSize);
        $detailEl->addAttribute('intMaxGuards', (string)$house->maxGuards);
        $detailEl->addAttribute('intMaxRooms', (string)$house->maxRooms);
        $detailEl->addAttribute('intMaxExtItems', (string)$house->maxExtItems);

        return $xml;
    }

}