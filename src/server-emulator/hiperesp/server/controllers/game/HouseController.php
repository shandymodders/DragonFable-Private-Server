<?php declare(strict_types=1);
namespace hiperesp\server\controllers\game;

use hiperesp\server\controllers\Controller;
use hiperesp\server\attributes\Inject;
use hiperesp\server\attributes\Request;
use hiperesp\server\enums\Input;
use hiperesp\server\enums\Output;
use hiperesp\server\projection\HouseProjection;
use hiperesp\server\services\CharacterService;
use hiperesp\server\services\HouseService;

class HouseController extends Controller {

    #[Inject] private CharacterService $characterService;
    #[Inject] private HouseService $houseService;

    #[Request(
        endpoint: '/cf-loadHouse.asp',
        inputType: Input::NINJA2,
        outputType: Output::XML
    )]
    public function load(\SimpleXMLElement $input): \SimpleXMLElement {
        $char = $this->characterService->auth($input);
        $charHouse = $this->houseService->getEquipped($char);
        return HouseProjection::instance()->loaded($char, $charHouse);
    }

}