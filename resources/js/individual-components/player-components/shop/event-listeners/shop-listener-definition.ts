import Listener from "../../../../game/lib/game/event-listeners/listener";
import Shop from "../shop";

export default interface ShopListenerDefinition extends Listener {
    /**
     *
     * Initialize the listener for the Shop Component.
     *
     * @param component
     * @param usrrId
     */
    initialize: (component: Shop, userId: number) => void;
}
