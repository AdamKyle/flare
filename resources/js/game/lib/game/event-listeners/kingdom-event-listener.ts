import Listener from "./listener";
import Kingdom from "../../../components/kingdoms/kingdom";
import SmallKingdom from "../../../components/kingdoms/small-kingdom";

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
