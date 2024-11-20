import Listener from "./listener";
import Game from "../../../game";

export default interface GameListener extends Listener {
    /**
     *
     * Initialize the listener for the Game Component.
     *
     * @param component
     * @param usrrId
     */
    initialize: (component: Game, userId?: number) => void;
}
