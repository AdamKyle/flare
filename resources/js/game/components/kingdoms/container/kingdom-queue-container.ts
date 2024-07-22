import { CoreContainer } from "../../../lib/containers/core-container";
import CancelBuildingInQueueAjax from "../ajax/cancel-building-in-queue-ajax";
import CancelBuildingRequestAjax from "../ajax/cancel-building-request-ajax";
import CancelUnitRequestAjax from "../ajax/cancel-unit-request-ajax";
import FetchBuildingQueuesAjax from "../ajax/fetch-building-queues-ajax";
import FetchKingdomsForSelectionAjax from "../ajax/fetch-kingdoms-for-selection-ajax";
import FetchUnitQueuesAjax from "../ajax/fetch-unit-queues-ajax";
import FetchUpgradableKingdomsAjax from "../ajax/fetch-upgradable-kingdoms-ajax";
import KingdomResourceTransferAjax from "../ajax/kingdom-resource-transfer-ajax";
import MakeCapitalCityAjax from "../ajax/make-capigtal-city-ajax";
import ProcessUnitRequestAjax from "../ajax/process-unit-request-ajax";
import ProcessUpgradeBuildingsAjax from "../ajax/process-upgrade-buildings-ajax";
import ResourceBuildingExpansionAjax from "../ajax/resource-building-expansion-ajax";
import UpgradeWithResourcesAjax from "../ajax/upgrade-with-resources-ajax";
import WalkAllKingdomsAjax from "../ajax/walk-all-kingdoms-ajax";
import kingdomQueueContainer from "../queues/container/kingdom-queue-container";
import kingdomEventContainer from "./kingdom-event-container";

/**
 * Register core dependencies here.
 *
 * These dependencies are used by other classes that are registered in the
 * container.
 *
 * @param container
 */
function kingdomContainer(container: CoreContainer) {
    // Let's register other container.
    kingdomQueueContainer(container);

    // Register the event container.
    kingdomEventContainer(container);

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

    container.register("process-recruit-units-ajax", {
        useClass: ProcessUnitRequestAjax,
    });

    container.register("fetch-building-queues-ajax", {
        useClass: FetchBuildingQueuesAjax,
    });

    container.register("fetch-unit-queues-ajax", {
        useClass: FetchUnitQueuesAjax,
    });

    container.register("cancel-building-request-ajax", {
        useClass: CancelBuildingRequestAjax,
    });

    container.register("cancel-unit-request-ajax", {
        useClass: CancelUnitRequestAjax,
    });

    container.register("cancel-building-in-queue-ajax", {
        useClass: CancelBuildingInQueueAjax,
    });

    container.register("resource-building-expansion-ajax", {
        useClass: ResourceBuildingExpansionAjax,
    });

    container.register("upgrade-with-resources-ajax", {
        useClass: UpgradeWithResourcesAjax,
    });
}

export default kingdomContainer;
