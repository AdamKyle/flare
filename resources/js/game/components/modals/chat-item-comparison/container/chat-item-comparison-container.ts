
import {CoreContainer} from "../../../../lib/containers/core-container";
import ChatItemComparisonAjax from "../../../../components/modals/chat-item-comparison/ajax/chat-item-comparison-ajax";

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
}

export default chatItemComparisonContainer;
