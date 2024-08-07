import Listener from "../../../lib/game/event-listeners/listener";
import UnitRecruitment from "../capital-city/unit-recruitment";
import BuildingQueuesTable from "../capital-city/building-queues-table";

export default interface CapitalCityBuildingQueueTableEventDefinition
    extends Listener {
    /**
     *
     * Initialize the listener for the Game Component.
     *
     * @param component
     * @param usrrId
     */
    initialize: (component: BuildingQueuesTable, userId: number) => void;
}
