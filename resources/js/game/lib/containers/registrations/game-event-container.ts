import {CoreContainer} from "../core-container";
import GameEventListeners from "../../game/event-listeners/game-event-listeners";
import MapListeners from "../../game/event-listeners/game/map-listeners";

/**
 * Register game event listeners here.
 *
 * @param container
 */
function gameEventContainer(container: CoreContainer) {

    // Game Event Listeners:
    // Classes are registered with their interface as their key.
    container.register('GameListeners', {useClass: MapListeners});

    // The Core Listener Class
    container.register('game-event-listeners', GameEventListeners);

}

export default gameEventContainer;
