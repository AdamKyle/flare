import BountyFightAjax from "../ajax/bounty-fight-ajax";
import FactionLoyaltyListeners from "../event-listeners/faction-loyalty-listeners";
import HandleCraftingAjax from "../ajax/handle-crafting-ajax";
function factionLoyaltyContainer(container) {
    container.register("bounty-fight-ajax", {
        useClass: BountyFightAjax,
    });
    container.register("handle-crafting-ajax", {
        useClass: HandleCraftingAjax,
    });
    container.register("FactionLoyaltyListener", {
        useClass: FactionLoyaltyListeners,
    });
}
export default factionLoyaltyContainer;
//# sourceMappingURL=faction-loyalty-container.js.map
