import KingdomResourceTransferAjax from "../ajax/kingdom-resource-transfer-ajax";
import { CoreContainer } from "../../../lib/containers/core-container";
import kingdomQueueContainer from "../queues/container/kingdom-queue-container";
import MakeCapitalCityAjax from "../ajax/make-capigtal-city-ajax";
import WalkAllKingdomsAjax from "../ajax/walk-all-kingdoms-ajax";
import FetchUpgradableKingdomsAjax from "../ajax/fetch-upgradable-kingdoms-ajax";
import ProcessUpgradeBuildingsAjax from "../ajax/process-upgrade-buildings-ajax";
import FetchKingdomsForSelectionAjax from "../ajax/fetch-kingdoms-for-selection-ajax";

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

    container.register("walk-all-kingdoms-ajax", {
        useClass: WalkAllKingdomsAjax,
    });

    container.register("fetch-upgradable-kingdoms-ajax", {
        useClass: FetchUpgradableKingdomsAjax,
    });

    container.register("fetch-kingdoms-for-selection-ajax", {
        useClass: FetchKingdomsForSelectionAjax,
    });

    container.register("process-upgrade-buildings-ajax", {
        useClass: ProcessUpgradeBuildingsAjax,
    });
}

export default kingdomContainer;
