import Listener from "../../../lib/game/event-listeners/listener";
import FactionFame from "../faction-fame";

export default interface FactionLoyaltyListener extends Listener {
    /**
     *
     * Initialize the listener for the Game Component.
     *
     * @param component
     * @param usrrId
     */
    initialize: (component: FactionFame, userId: number) => void;
}
