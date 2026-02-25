<?php declare(strict_types=1);
namespace hiperesp\server\projection;

use hiperesp\server\vo\HouseItemShopVO;

class HouseItemShopProjection extends Projection {

    public function loaded(HouseItemShopVO $shop): \SimpleXMLElement {

        $xml = new \SimpleXMLElement('<houseitemshop/>');
        $shopEl = $xml->addChild('houseitemshop');
        $shopEl->addAttribute('houseItemShopID', (string)$shop->id);
        $shopEl->addAttribute('strName', $shop->name);

        foreach($shop->getItems() as $houseItem) {
            $itemEl = $shopEl->addChild('houseitems');

            $itemEl->addAttribute('HouseItemID',        (string)$houseItem->id);
            $itemEl->addAttribute('strItemName',        $houseItem->name);
            $itemEl->addAttribute('strItemDescription', $houseItem->description);
            $itemEl->addAttribute('bitVisible',         (string)$houseItem->visible);
            $itemEl->addAttribute('bitDestroyable',     (string)$houseItem->destroyable);
            $itemEl->addAttribute('bitEquippable',      (string)$houseItem->equippable);
            $itemEl->addAttribute('bitRandomDrop',      (string)$houseItem->randomDrop);
            $itemEl->addAttribute('bitSellable',        (string)$houseItem->sellable);
            $itemEl->addAttribute('bitDragonAmulet',    (string)$houseItem->dragonAmulet);
            $itemEl->addAttribute('bitEnc',             (string)$houseItem->enc);
            $itemEl->addAttribute('intCost',            (string)$houseItem->cost);
            $itemEl->addAttribute('intCurrency',        (string)$houseItem->currency);
            $itemEl->addAttribute('intMaxStackSize',    (string)$houseItem->maxStackSize);
            $itemEl->addAttribute('intRarity',          (string)$houseItem->rarity);
            $itemEl->addAttribute('intLevel',           (string)$houseItem->level);
            $itemEl->addAttribute('intMaxLevel',        (string)$houseItem->maxLevel);
            $itemEl->addAttribute('intCategory',        (string)$houseItem->category);
            $itemEl->addAttribute('intEquipSpot',       (string)$houseItem->equipSpot);
            $itemEl->addAttribute('intType',            (string)$houseItem->itemType);
            $itemEl->addAttribute('bitRandom',          (string)$houseItem->random);
            $itemEl->addAttribute('intElement',         (string)$houseItem->element);
            $itemEl->addAttribute('strType',            $houseItem->type);
            $itemEl->addAttribute('strFileName',        $houseItem->swf);
        }

        return $xml;
    }

}