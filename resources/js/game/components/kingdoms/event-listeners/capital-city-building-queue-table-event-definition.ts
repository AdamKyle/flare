import Listener from "../../../lib/game/event-listeners/listener";
import UnitRecruitment from "../capital-city/unit-recruitment";
import BuildingsInQueue from "../capital-city/buildings-in-queue";

export default interface CapitalCityBuildingQueueTableEventDefinition
    extends Listener {
    /**
     *
     * Initialize the listener for the Game Component.
     *
     * @param component
     * @param usrrId
     */
    initialize: (component: BuildingsInQueue, userId: number) => void;
}
