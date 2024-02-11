import {CoreContainer} from "../core-container";
import GameEventListeners from "../../game/event-listeners/game-event-listeners";
import MapListeners from "../../game/event-listeners/game/map-listeners";
import CharacterListeners from "../../game/event-listeners/game/character-listeners";
import MonsterListeners from "../../game/event-listeners/game/monster-listeners";
import KingdomListeners from "../../game/event-listeners/game/kingdom-listeners";
import ActionListeners from "../../game/event-listeners/game/action-listeners";
import QuestListeners from "../../game/event-listeners/game/quest-listeners";

/**
 * Register game event listeners here.
 *
 * @param container
 */
function gameEventContainer(container: CoreContainer) {

    // Game Event Listeners:
    // Classes are registered with their interface as their key.
    container.register('GameListener', {useClass: MapListeners});
    container.register('GameListener', {useClass: CharacterListeners});
    container.register('GameListener', {useClass: MonsterListeners});
    container.register('GameListener', {useClass: KingdomListeners});
    container.register('GameListener', {useClass: ActionListeners});
    container.register('GameListener', {useClass: QuestListeners});

    // The Core Listener Class
    container.register('game-event-listeners', GameEventListeners);

}

export default gameEventContainer;
