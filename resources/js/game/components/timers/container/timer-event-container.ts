import { CoreContainer } from "../../../lib/containers/core-container";
import ActionTimerListeners from "../event-listeners/action-timer-listeners";

/**
 * Register game event listeners here.
 *
 * @param container
 */
function timerEventContainer(container: CoreContainer) {
    container.register("ActionTimerListener", {
        useClass: ActionTimerListeners,
    });
}

export default timerEventContainer;
