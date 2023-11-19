import {CoreContainer} from "../core-container";
import GameEventListeners from "../../game/event-listeners/game-event-listeners";
import MapListeners from "../../game/event-listeners/game/map-listeners";
import CharacterListeners from "../../game/event-listeners/game/character-listeners";
import MonsterListeners from "../../game/event-listeners/game/monster-listeners";
import KingdomListeners from "../../game/event-listeners/game/kingdom-listeners";
import ActionListeners from "../../game/event-listeners/game/action-listeners";

/**
 * Register game event listeners here.
 *
 * @param container
 */
function gameEventContainer(container: CoreContainer) {

    // Game Event Listeners:
    // Classes are registered with their interface as their key.
    container.register('GameListeners', {useClass: MapListeners});
    container.register('GameListeners', {useClass: CharacterListeners});
    container.register('GameListeners', {useClass: MonsterListeners});
    container.register('GameListeners', {useClass: KingdomListeners});
    container.register('GameListeners', {useClass: ActionListeners});

    // The Core Listener Class
    container.register('game-event-listeners', GameEventListeners);

}

export default gameEventContainer;
