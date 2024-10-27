import Listener from "../../../lib/game/event-listeners/listener";
import UnitQueue from "../capital-city/partials/unit-management/unit-queue";

export default interface CapitalCityUnitQueueTableEventDefinition
    extends Listener {
    /**
     *
     * Initialize the listener for the Game Component.
     *
     * @param component
     * @param usrrId
     */
    initialize: (component: UnitQueue, userId: number) => void;
}
