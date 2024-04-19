import Listener from "./listener";
import Kingdom from "../../../sections/kingdoms/kingdom";
import SmallKingdom from "../../../sections/kingdoms/small-kingdom";

export default interface KingdomEventListener extends Listener {

    /**
     *
     * Initialize the listener for the Game Component.
     *
     * @param component
     * @param usrrId
     */
    initialize: (component: Kingdom | SmallKingdom, userId: number) => void;
}
