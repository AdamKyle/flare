import Listener from "../../../../../game/lib/game/event-listeners/listener";
import KingdomTops from "../kingdom-tops";

export default interface KingdomTopsListenerDefinition extends Listener {
    initialize: (component: KingdomTops) => void;
}
