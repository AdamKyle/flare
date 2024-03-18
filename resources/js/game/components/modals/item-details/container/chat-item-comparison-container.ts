
import {CoreContainer} from "../../../../lib/containers/core-container";
import ItemHolyEffects from "../values/item-holy-effects";
import InventoryComparisonActionsAjax from "../ajax/inventory-comparison-actions-ajax";
import ItemComparisonAjax from "../ajax/item-comparison-ajax";

/**
 * Register core dependencies here.
 *
 * These dependencies are used by other classes that are registered in the
 * container.
 *
 * @param container
 */
function chatItemComparisonContainer(container: CoreContainer) {
    container.register('item-details-ajax', {
        useClass: ItemComparisonAjax
    });

    container.register('inventory-comparison-action-ajax', {
        useClass: InventoryComparisonActionsAjax
    });

    container.register('item-holy-effects', {
        useClass: ItemHolyEffects
    });
}

export default chatItemComparisonContainer;
