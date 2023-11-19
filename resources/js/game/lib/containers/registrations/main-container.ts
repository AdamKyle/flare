import {CoreContainer} from "../core-container";
import CoreEventListener from "../../game/event-listeners/core-event-listener";

/**
 * Register core dependencies here.
 *
 * These dependencies are used by other classes that are registered in the
 * container.
 *
 * @param container
 */
function mainContainer(container: CoreContainer) {

    container.register('core-event-listener', {
        useClass: CoreEventListener
    });
}

export default mainContainer;
