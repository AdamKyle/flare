import ActionTimerListeners from "../event-listeners/action-timer-listeners";
function timerEventContainer(container) {
    container.register("ActionTimerListener", {
        useClass: ActionTimerListeners,
    });
}
export default timerEventContainer;
//# sourceMappingURL=timer-event-container.js.map
