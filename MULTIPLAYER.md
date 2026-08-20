# Realtime multiplayer presence (experimental)

This branch adds a lightweight realtime presence/position layer for DragonFable maps.

## What it does

- Authenticates position updates using the existing account token + character ownership checks.
- Sends local scene coordinates about 5 times per second.
- Streams active players to each browser using Server-Sent Events (SSE).
- Only renders players whose `mapKey` matches the local player's current map.
- Removes disconnected/stale players automatically after a short TTL.
- Renders a lightweight in-game player silhouette + character name inside `stage.players`, with client-side interpolation for smoother movement.

## Files added

- `src/server-emulator/hiperesp/server/services/MultiplayerService.php`
- `src/server-emulator/hiperesp/server/controllers/web/MultiplayerController.php`
- `src/web/assets/js/game.multiplayer.js`

## SWF patch requirement

The browser/server pieces are not enough by themselves: the game SWF must expose the live player coordinates and three callbacks used to render remote players.

The existing patch file was extended:

- `dev-tools/patch-new-swf/patches/external-feature-chat/replace.txt`

Run the normal `dev-tools/patch-new-swf/patch-swf.php` workflow with FFDec, then deploy the newly generated `game15_9_59-patched.swf` to `src/cdn/gamefiles/`.

The project already documents FFDec usage in `DEV.md` and the patch script prints the exact FFDec export/import commands.

## Realtime model

This is a stable first stage, not a full MMO conversion. Remote players are represented by a lightweight in-map silhouette and name. Full class/armor/weapon/hair replication would require an additional appearance-loading layer in AVM1.
