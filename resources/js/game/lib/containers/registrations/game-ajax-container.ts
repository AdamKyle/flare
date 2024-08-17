import { CoreContainer } from "../core-container";
import TurnOffUserIntroFlag from "../../game/ajax/turn-off-user-intro-flag";

/**
 * Register ajax dependencies.
 *
 * @param container
 */
function gameAjaxContainer(container: CoreContainer) {
    container.register("turn-off-user-intro-flag-ajax", {
        useClass: TurnOffUserIntroFlag,
    });
}

export default gameAjaxContainer;
