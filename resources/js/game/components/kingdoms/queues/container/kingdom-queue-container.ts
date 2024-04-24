import { CoreContainer } from "../../../../lib/containers/core-container";
import CancellationAjax from "../ajax/cancellation-ajax";

/**
 * Register core dependencies here.
 *
 * These dependencies are used by other classes that are registered in the
 * container.
 *
 * @param container
 */
function kingdomQueueContainer(container: CoreContainer) {
    container.register("kingdom-cancellation-ajax", {
        useClass: CancellationAjax,
    });
}

export default kingdomQueueContainer;
