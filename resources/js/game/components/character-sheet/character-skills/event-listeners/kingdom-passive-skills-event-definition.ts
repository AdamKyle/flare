import Listener from "../../../../lib/game/event-listeners/listener";
import KingdomPassives from "../kingdom-passives";

export default interface KingdomPassiveSkillsEventDefinition extends Listener {
    /**
     *
     * Initialize the listener for the Game Component.
     *
     * @param component
     * @param usrrId
     */
    initialize: (component: KingdomPassives, userId: number) => void;
}
