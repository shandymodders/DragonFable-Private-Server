<?php declare(strict_types=1);
namespace hiperesp\server\controllers\game;

use hiperesp\server\controllers\Controller;
use hiperesp\server\attributes\Inject;
use hiperesp\server\attributes\Request;
use hiperesp\server\enums\Input;
use hiperesp\server\enums\Output;
use hiperesp\server\projection\HouseShopProjection;
use hiperesp\server\services\CharacterService;
use hiperesp\server\services\HouseShopService;

class HouseShopController extends Controller {

    #[Inject] private CharacterService $characterService;
    #[Inject] private HouseShopService $houseShopService;

    #[Request(
        endpoint: '/cf-houseshopload.asp',
        inputType: Input::NINJA2,
        outputType: Output::XML
    )]
    public function load(\SimpleXMLElement $input): \SimpleXMLElement {
        $char = $this->characterService->auth($input);
        $shop = $this->houseShopService->getShop((int)$input->intShopID);
        $charHouses = $this->houseShopService->getOwnedHouses($char);
        return HouseShopProjection::instance()->loaded($shop, $charHouses);
    }

    #[Request(
        endpoint: '/cf-buyhouse.asp',
        inputType: Input::NINJA2,
        outputType: Output::XML
    )]
    public function buy(\SimpleXMLElement $input): \SimpleXMLElement {
        $char = $this->characterService->auth($input);
        $charHouse = $this->houseShopService->buy($char, (int)$input->intHouseShopID, (int)$input->intHouseID);

        $this->houseShopService->equip($char, $charHouse->id);

        return HouseShopProjection::instance()->bought($charHouse);
    }

    #[Request(
        endpoint: '/cf-sellhouse.asp',
        inputType: Input::NINJA2,
        outputType: Output::XML
    )]
    public function sell(\SimpleXMLElement $input): \SimpleXMLElement {
        $char = $this->characterService->auth($input);
        $this->houseShopService->sell($char, (int)$input->intCharHouseID);
        return HouseShopProjection::instance()->sold();
    }

    #[Request(
        endpoint: '/cf-equiphouse.asp',
        inputType: Input::NINJA2,
        outputType: Output::XML
    )]
    public function equip(\SimpleXMLElement $input): \SimpleXMLElement {
        $char = $this->characterService->auth($input);
        $this->houseShopService->equip($char, (int)$input->intCharHouseID);
        return HouseShopProjection::instance()->equipped();
    }
}