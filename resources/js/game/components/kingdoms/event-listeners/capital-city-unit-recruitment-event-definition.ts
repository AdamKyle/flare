import Listener from "../../../lib/game/event-listeners/listener";
import UnitRecruitment from "../capital-city/partials/unit-management/unit-recruitment";

export default interface CapitalCityUnitRecruitmentEventDefinition
    extends Listener {
    /**
     *
     * Initialize the listener for the Game Component.
     *
     * @param component
     * @param usrrId
     */
    initialize: (component: UnitRecruitment, userId: number) => void;
}
