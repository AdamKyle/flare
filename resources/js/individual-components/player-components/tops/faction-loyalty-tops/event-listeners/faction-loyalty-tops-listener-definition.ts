import Listener from "../../../../../game/lib/game/event-listeners/listener";
import FactionLoyaltyTops from "../faction-loyalty-tops";

export default interface FactionLoyaltyTopsListenerDefinition extends Listener {
    initialize: (component: FactionLoyaltyTops) => void;
}
