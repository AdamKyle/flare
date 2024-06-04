import KingdomResourceTransferAjax from "../ajax/kingdom-resource-transfer-ajax";
import kingdomQueueContainer from "../queues/container/kingdom-queue-container";
function kingdomContainer(container) {
    kingdomQueueContainer(container);
    container.register("kingdom-resource-transfer-request-ajax", {
        useClass: KingdomResourceTransferAjax,
    });
}
export default kingdomContainer;
//# sourceMappingURL=kingdom-queue-container.js.map
