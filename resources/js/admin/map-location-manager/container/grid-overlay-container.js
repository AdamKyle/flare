import { container } from "tsyringe";
import MouseHandlers from "../grid/mouse-handlers";
import ToolTipHandler from "../grid/tool-tip-handler";
var GridOverlayContainer = (function () {
    function GridOverlayContainer() {
        this.register("mouse-handlers", {
            useClass: MouseHandlers,
        });
        this.register("tool-tip-handler", {
            useClass: ToolTipHandler,
        });
    }
    GridOverlayContainer.getInstance = function () {
        if (!GridOverlayContainer.instance) {
            GridOverlayContainer.instance = new GridOverlayContainer();
        }
        return GridOverlayContainer.instance;
    };
    GridOverlayContainer.prototype.fetch = function (token) {
        return container.resolve(token);
    };
    GridOverlayContainer.prototype.register = function (key, service) {
        container.register(key, { useValue: service });
    };
    return GridOverlayContainer;
})();
var dependencyRegistry;
var gridOverLayContainer = function () {
    if (!dependencyRegistry) {
        dependencyRegistry = new GridOverlayContainer();
    }
    return dependencyRegistry;
};
export { gridOverLayContainer, GridOverlayContainer };
//# sourceMappingURL=grid-overlay-container.js.map
