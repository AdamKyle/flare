import {CoreContainer} from "../lib/containers/core-container";
import kingdomQueueContainer from "../sections/kingdoms/queues/container/kingdom-queue-container";

function containerRegistry(coreContainer: CoreContainer): void {
    kingdomQueueContainer(coreContainer);
}

export {containerRegistry};
