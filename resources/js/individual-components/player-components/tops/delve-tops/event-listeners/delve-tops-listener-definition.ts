import Listener from "../../../../../game/lib/game/event-listeners/listener";
import DelveTops from "../delve-tops";

export default interface DelveTopsListenerDefinition extends Listener {
    initialize: (component: DelveTops) => void;
}
