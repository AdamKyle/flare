import CapitalCityBuildingUpgradeRepairTableEvent from "../event-listeners/capital-city-building-upgrade-repair-table-event";
import { CoreContainer } from "../../../lib/containers/core-container";
import CapitalCityBuildingQueuesTableEvent from "../event-listeners/capital-city-building-queues-table-event";

/**
 * Register game event listeners here.
 *
 * @param container
 */
function kingdomEventContainer(container: CoreContainer) {
    // Register the Capital City Events for when the building upgrade, repair table updates.
    container.register("CapitalCityBuildingUpgradeRepairTableEventDefinition", {
        useClass: CapitalCityBuildingUpgradeRepairTableEvent,
    });

    container.register("CapitalCityBuildingQueueTableEventDefinition", {
        useeClass: CapitalCityBuildingQueuesTableEvent,
    });
}

export default kingdomEventContainer;
