<?php declare(strict_types=1);
namespace hiperesp\server\controllers\web;

use hiperesp\server\attributes\Inject;
use hiperesp\server\attributes\Request;
use hiperesp\server\controllers\Controller;
use hiperesp\server\enums\Input;
use hiperesp\server\enums\Output;
use hiperesp\server\projection\PvpProjection;
use hiperesp\server\services\WorldService;

class WorldController extends Controller {

    #[Inject] private WorldService $worldService;

    #[Request(
        endpoint: '/world/update',
        inputType: Input::JSON,
        outputType: Output::NONE
    )]
    public function update(array $input): void {
        $this->worldService->update(
            token: (string)($input['token'] ?? ''),
            state: $input,
        );
    }

    #[Request(
        endpoint: '/world/leave',
        inputType: Input::JSON,
        outputType: Output::NONE
    )]
    public function leave(array $input): void {
        $this->worldService->leave((string)($input['token'] ?? ''));
    }

    #[Request(
        endpoint: '/world/appearance',
        inputType: Input::QUERY,
        outputType: Output::JSON
    )]
    public function appearance(array $input): array {
        $char = $this->worldService->getAppearance(
            token: (string)($input['token'] ?? ''),
            charId: (int)($input['charId'] ?? 0),
        );

        $projection = PvpProjection::instance()->loaded($char);
        $xml = (string)$projection->asXML();

        // Version only the visual appearance. PvPProjection also contains HP, MP,
        // currency and other changing stats; hashing the entire XML caused remote
        // avatars to be destroyed/rebuilt even when their appearance did not change.
        $character = $projection->character;
        $visual = [
            'CharID' => (string)$character['CharID'],
            'strGender' => (string)$character['strGender'],
            'ClassID' => (string)$character['ClassID'],
            'strClassFileName' => (string)$character['strClassFileName'],
            'strHairFileName' => (string)$character['strHairFileName'],
            'intHairFrame' => (string)$character['intHairFrame'],
            'intColorHair' => (string)$character['intColorHair'],
            'intColorSkin' => (string)$character['intColorSkin'],
            'intColorBase' => (string)$character['intColorBase'],
            'intColorTrim' => (string)$character['intColorTrim'],
            'items' => [],
        ];

        foreach($character->items as $item) {
            $spot = \strtolower((string)$item['strEquipSpot']);
            if(!\in_array($spot, ['weapon', 'back', 'cape', 'head', 'helm'], true)) {
                continue;
            }
            if((int)$item['bitEquipped'] !== 1) {
                continue;
            }
            $visual['items'][] = [
                'spot' => $spot,
                'id' => (string)$item['ItemID'],
                'file' => (string)$item['strFileName'],
                'type' => (string)$item['strType'],
                'itemType' => (string)$item['strItemType'],
            ];
        }
        \usort($visual['items'], static fn(array $a, array $b): int => ($a['spot'].'|'.$a['id']) <=> ($b['spot'].'|'.$b['id']));
        $version = \sha1((string)\json_encode($visual, \JSON_UNESCAPED_SLASHES));

        return [
            'charId' => $char->id,
            'version' => $version,
            'xml' => $xml,
        ];
    }

    #[Request(
        endpoint: '/world/stream',
        inputType: Input::QUERY,
        outputType: Output::LOOP_EVENT_SOURCE
    )]
    public function stream(array $input): callable {
        return $this->worldService->eventSource(
            token: (string)($input['token'] ?? ''),
            map: (string)($input['map'] ?? ''),
        );
    }
}
