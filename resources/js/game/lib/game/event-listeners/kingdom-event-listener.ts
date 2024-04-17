import Listener from "./listener";
import Kingdom from "../../../sections/kingdoms/kingdom";

export default interface KingdomEventListener extends Listener {

    /**
     *
     * Initialize the listener for the Game Component.
     *
     * @param component
     * @param usrrId
     */
    initialize: (component: Kingdom, userId: number) => void;
}
