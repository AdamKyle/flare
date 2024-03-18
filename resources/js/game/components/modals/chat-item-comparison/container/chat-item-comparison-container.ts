
import {CoreContainer} from "../../../../lib/containers/core-container";
import ChatItemComparisonAjax from "../../../../components/modals/chat-item-comparison/ajax/chat-item-comparison-ajax";
import ItemHolyEffects from "../values/item-holy-effects";
import InventoryComparisonActionsAjax from "../ajax/inventory-comparison-actions-ajax";

/**
 * Register core dependencies here.
 *
 * These dependencies are used by other classes that are registered in the
 * container.
 *
 * @param container
 */
function chatItemComparisonContainer(container: CoreContainer) {
    container.register('chat-item-comparison-ajax', {
        useClass: ChatItemComparisonAjax
    });

    container.register('inventory-comparison-action-ajax', {
        useClass: InventoryComparisonActionsAjax
    });

    container.register('item-holy-effects', {
        useClass: ItemHolyEffects
    });
}

export default chatItemComparisonContainer;
