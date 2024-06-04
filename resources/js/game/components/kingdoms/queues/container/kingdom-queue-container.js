import CancellationAjax from "../ajax/cancellation-ajax";
function kingdomQueueContainer(container) {
    container.register("kingdom-cancellation-ajax", {
        useClass: CancellationAjax,
    });
}
export default kingdomQueueContainer;
//# sourceMappingURL=kingdom-queue-container.js.map
