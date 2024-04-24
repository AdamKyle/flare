import { CoreContainer } from "../core-container";
import CoreEventListener from "../../game/event-listeners/core-event-listener";
import Ajax from "../../ajax/ajax";

/**
 * Register core dependencies here.
 *
 * These dependencies are used by other classes that are registered in the
 * container.
 *
 * @param container
 */
function mainContainer(container: CoreContainer) {
    container.register("core-event-listener", {
        useClass: CoreEventListener,
    });

    container.register("AjaxInterface", {
        useClass: Ajax,
    });
}

export default mainContainer;
