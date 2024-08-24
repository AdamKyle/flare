import { CoreContainer } from "../../../lib/containers/core-container";
import SuggestionsAndBugsAjax from "../ajax/suggestions-and-bugs-ajax";

/**
 * Register game event listeners here.
 *
 * @param container
 */
function suggestionsContainer(container: CoreContainer) {
    container.register("suggestions-and-bugs-ajax", {
        useClass: SuggestionsAndBugsAjax,
    });
}

export default suggestionsContainer;
