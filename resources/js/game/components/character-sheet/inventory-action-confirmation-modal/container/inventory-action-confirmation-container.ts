import ModalPropsBuilder from "../helpers/modal-props-builder";
import { CoreContainer } from "../../../../lib/containers/core-container";

/**
 * Register core dependencies here.
 *
 * These dependencies are used by other classes that are registered in the
 * container.
 *
 * @param container
 */
function inventoryActionConfirmationContainer(container: CoreContainer) {
    container.register("modal-props-builder", {
        useClass: ModalPropsBuilder,
    });
}

export default inventoryActionConfirmationContainer;
