import { CoreContainer } from "../../../lib/containers/core-container";
import BountyFightAjax from "../ajax/bounty-fight-ajax";
import FactionLoyaltyListeners from "../event-listeners/faction-loyalty-listeners";

/**
 * Register core dependencies here.
 *
 * These dependencies are used by other classes that are registered in the
 * container.
 *
 * @param container
 */
function factionLoyaltyContainer(container: CoreContainer) {
    container.register("bounty-fight-ajax", {
        useClass: BountyFightAjax,
    });

    container.register("FactionLoyaltyListener", {
        useClass: FactionLoyaltyListeners,
    });
}

export default factionLoyaltyContainer;
