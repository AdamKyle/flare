import kingdomQueueContainer from "../components/kingdoms/queues/container/kingdom-queue-container";
import chatItemComparisonContainer from "../components/modals/item-details/container/chat-item-comparison-container";
import {CoreContainer} from "../lib/containers/core-container";

function containerRegistry(coreContainer: CoreContainer): void {

    // Item Comparison
    chatItemComparisonContainer(coreContainer);

    // Kingdoms
    kingdomQueueContainer(coreContainer);
}

export {containerRegistry};
