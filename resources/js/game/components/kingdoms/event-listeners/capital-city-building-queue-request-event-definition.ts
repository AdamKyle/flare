import Listener from "../../../lib/game/event-listeners/listener";
import UnitRecruitment from "../capital-city/partials/unit-management/unit-recruitment";
import BuildingsInQueue from "../capital-city/buildings-in-queue";
import BuildingsToUpgradeSection from "../capital-city/buildings-to-upgrade-section";

export default interface CapitalCityBuildingQueueRequestEventDefinition
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
