import Listener from "../../../../../game/lib/game/event-listeners/listener";
import ExplorationTops from "../exploration-tops";

export default interface ExplorationTopsListenerDefinition extends Listener {
    initialize: (component: ExplorationTops) => void;
}
