import Listener from "../../../lib/game/event-listeners/listener";
import BuildingsInQueue from "../capital-city/buildings-in-queue";
import UnitQueuesTable from "../capital-city/unit-queues-table";
import UnitRecruitment from "../capital-city/unit-recruitment";

export default interface CapitalCityUnitQueueTableEventDefinition
    extends Listener {
    /**
     *
     * Initialize the listener for the Game Component.
     *
     * @param component
     * @param usrrId
     */
    initialize: (component: UnitQueuesTable, userId: number) => void;
}
