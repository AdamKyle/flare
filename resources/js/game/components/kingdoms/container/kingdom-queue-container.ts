import KingdomResourceTransferAjax from "../ajax/kingdom-resource-transfer-ajax";
import { CoreContainer } from "../../../lib/containers/core-container";
import kingdomQueueContainer from "../queues/container/kingdom-queue-container";
import MakeCapitalCityAjax from "../ajax/make-capigtal-city-ajax";

/**
 * Register core dependencies here.
 *
 * These dependencies are used by other classes that are registered in the
 * container.
 *
 * @param container
 */
function kingdomContainer(container: CoreContainer) {
    // Let's register other containers here, that might live in sub modules:
    kingdomQueueContainer(container);

    container.register("kingdom-resource-transfer-request-ajax", {
        useClass: KingdomResourceTransferAjax,
    });

    container.register("make-capital-city-ajax", {
        useClass: MakeCapitalCityAjax,
    });
}

export default kingdomContainer;
