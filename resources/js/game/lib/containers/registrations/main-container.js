import CoreEventListener from "../../game/event-listeners/core-event-listener";
import Ajax from "../../ajax/ajax";
function mainContainer(container) {
    container.register("core-event-listener", {
        useClass: CoreEventListener,
    });
    container.register("AjaxInterface", {
        useClass: Ajax,
    });
}
export default mainContainer;
//# sourceMappingURL=main-container.js.map
