import {CoreContainer} from "../lib/containers/core-container";
import kingdomQueueContainer from "../sections/kingdoms/queues/container/kingdom-queue-container";
import chatItemComparisonContainer from "../sections/kingdoms/queues/container/kingdom-queue-container";

function containerRegistry(coreContainer: CoreContainer): void {

    // Item Comparison
    chatItemComparisonContainer(coreContainer);

    // Kingdoms
    kingdomQueueContainer(coreContainer);
}

export {containerRegistry};
