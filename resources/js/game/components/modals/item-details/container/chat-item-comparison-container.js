import ItemHolyEffects from "../values/item-holy-effects";
import InventoryComparisonActionsAjax from "../ajax/inventory-comparison-actions-ajax";
import ItemComparisonAjax from "../ajax/item-comparison-ajax";
function chatItemComparisonContainer(container) {
    container.register("item-details-ajax", {
        useClass: ItemComparisonAjax,
    });
    container.register("inventory-comparison-action-ajax", {
        useClass: InventoryComparisonActionsAjax,
    });
    container.register("item-holy-effects", {
        useClass: ItemHolyEffects,
    });
}
export default chatItemComparisonContainer;
//# sourceMappingURL=chat-item-comparison-container.js.map
