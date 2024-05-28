import Listener from "../../../lib/game/event-listeners/listener";
import ActionsTimers from "../actions-timers";

export default interface ActionTimerListener extends Listener {
    /**
     *
     * Initialize the listener for the Game Component.
     *
     * @param component
     * @param usrrId
     */
    initialize: (component: ActionsTimers, userId: number) => void;
}
