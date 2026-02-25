<?php declare(strict_types=1);
namespace hiperesp\server\controllers\game;

use hiperesp\server\controllers\Controller;
use hiperesp\server\attributes\Inject;
use hiperesp\server\attributes\Request;
use hiperesp\server\enums\Input;
use hiperesp\server\enums\Output;
use hiperesp\server\projection\CharHouseItemProjection;
use hiperesp\server\projection\HouseItemShopProjection;
use hiperesp\server\services\CharacterService;
use hiperesp\server\services\HouseItemShopService;

class HouseItemShopController extends Controller {

    #[Inject] private HouseItemShopService $houseItemShopService;
    #[Inject] private CharacterService $characterService;

    #[Request(
        endpoint: '/cf-loadhouseitemshop.asp',
        inputType: Input::NINJA2,
        outputType: Output::XML
    )]
    public function load(\SimpleXMLElement $input): \SimpleXMLElement {
        $shop = $this->houseItemShopService->getShop((int)$input->intHouseItemShopID);
        if($shop == null) {
            return new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><houseitemshop xmlns:sql="urn:schemas-microsoft-com:xml-sql"/>');
        }
        return HouseItemShopProjection::instance()->loaded($shop);
    }

    #[Request(
        endpoint: '/cf-buyhouseitem.asp',
        inputType: Input::NINJA2,
        outputType: Output::XML
    )]
    public function buy(\SimpleXMLElement $input): \SimpleXMLElement {
        $char = $this->characterService->auth($input);

        $charHouseItem = $this->houseItemShopService->buy(
            $char,
            shopId:      (int)$input->intHouseItemShopID,
            houseItemId: (int)$input->intHouseItemID
        );

        return CharHouseItemProjection::instance()->bought($charHouseItem);
    }

    #[Request(
        endpoint: '/cf-sellhouseitem.asp',
        inputType: Input::NINJA2,
        outputType: Output::XML
    )]
    public function sell(\SimpleXMLElement $input): \SimpleXMLElement {
        $char = $this->characterService->auth($input);

        $this->houseItemShopService->sell(
            $char,
            charHouseItemId: (int)$input->intCharHouseItemID,
            returnPercent:   (int)$input->intReturnPer
        );

        return CharHouseItemProjection::instance()->sold();
    }

    #[Request(
        endpoint: '/cf-equiphouseitem.asp',
        inputType: Input::NINJA2,
        outputType: Output::NINJA2XML
    )]
    public function equip(\SimpleXMLElement $input): \SimpleXMLElement {
        $char = $this->characterService->auth($input);

        $this->houseItemShopService->equip(
            $char,
            charHouseItemId: (int)$input->intCharHouseItemID,
            slot:            (int)$input->intEquipSlot
        );

        return CharHouseItemProjection::instance()->equipped();
    }

    #[Request(
        endpoint: '/cf-unequiphouseitem.asp',
        inputType: Input::NINJA2,
        outputType: Output::NINJA2XML
    )]
    public function unequip(\SimpleXMLElement $input): \SimpleXMLElement {
        $char = $this->characterService->auth($input);

        $this->houseItemShopService->unequip(
            $char,
            slot: (int)$input->intEquipSlot
        );

        return CharHouseItemProjection::instance()->unequipped();
    }

}