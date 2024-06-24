import CapitalCityBuildingUpgradeRepairTableEvent from "../event-listeners/capital-city-building-upgrade-repair-table-event";
import { CoreContainer } from "../../../lib/containers/core-container";

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
}

export default kingdomEventContainer;
