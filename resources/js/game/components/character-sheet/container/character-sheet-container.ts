import inventoryActionConfirmationContainer from "../inventory-action-confirmation-modal/container/inventory-action-confirmation-container";
import { CoreContainer } from "../../../lib/containers/core-container";

/**
 * Register core dependencies here.
 *
 * These dependencies are used by other classes that are registered in the
 * container.
 *
 * @param container
 */
function characterSheetContainer(container: CoreContainer) {
    inventoryActionConfirmationContainer(container);
}

export default characterSheetContainer;
