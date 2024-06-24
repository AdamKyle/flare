import BuildingsToUpgradeSection from "../capital-city/buildings-to-upgrade-section";
import Listener from "../../../lib/game/event-listeners/listener";

export default interface CapitalCityBuildingUpgradeRepairTableEventDefinition
    extends Listener {
    /**
     *
     * Initialize the listener for the Game Component.
     *
     * @param component
     * @param usrrId
     */
    initialize: (component: BuildingsToUpgradeSection, userId: number) => void;
}
