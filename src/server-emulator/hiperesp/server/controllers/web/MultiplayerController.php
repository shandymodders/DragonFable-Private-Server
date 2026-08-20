<?php declare(strict_types=1);
namespace hiperesp\server\controllers\web;

use hiperesp\server\attributes\Inject;
use hiperesp\server\attributes\Request;
use hiperesp\server\controllers\Controller;
use hiperesp\server\enums\Input;
use hiperesp\server\enums\Output;
use hiperesp\server\exceptions\DFException;
use hiperesp\server\services\CharacterService;
use hiperesp\server\services\MultiplayerService;

class MultiplayerController extends Controller {

    #[Inject] private CharacterService $characterService;
    #[Inject] private MultiplayerService $multiplayerService;

    #[Request(
        endpoint: '/multiplayer/update',
        inputType: Input::JSON,
        outputType: Output::NONE
    )]
    public function update(?array $input): void {
        if(!$input || empty($input['token']) || empty($input['charId'])) {
            throw new DFException(DFException::BAD_REQUEST);
        }

        $char = $this->characterService->auth((string)$input['token'], (int)$input['charId']);

        $this->multiplayerService->update(
            char: $char,
            mapKey: (string)($input['mapKey'] ?? ''),
            x: (float)($input['x'] ?? 0),
            y: (float)($input['y'] ?? 0),
            scaleX: (float)($input['scaleX'] ?? 100),
            scaleY: (float)($input['scaleY'] ?? 100),
        );
    }

    #[Request(
        endpoint: '/multiplayer/stream',
        inputType: Input::QUERY,
        outputType: Output::LOOP_EVENT_SOURCE
    )]
    public function stream(array $input): callable {
        $token = (string)($input['token'] ?? '');
        if($token === '') {
            throw new DFException(DFException::BAD_REQUEST);
        }
        return $this->multiplayerService->eventSource($token);
    }

    #[Request(
        endpoint: '/multiplayer/leave',
        inputType: Input::JSON,
        outputType: Output::NONE
    )]
    public function leave(?array $input): void {
        $token = (string)($input['token'] ?? '');
        if($token === '') {
            return;
        }
        $this->multiplayerService->leave($token);
    }
}
