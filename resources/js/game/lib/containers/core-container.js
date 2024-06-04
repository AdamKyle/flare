import { container } from "tsyringe";
import mainContainer from "./registrations/main-container";
import gameEventContainer from "./registrations/game-event-container";
import { containerRegistry } from "../../configuration/container-registry";
var CoreContainer = (function () {
    function CoreContainer() {
        mainContainer(this);
        gameEventContainer(this);
        containerRegistry(this);
    }
    CoreContainer.getInstance = function () {
        if (!CoreContainer.instance) {
            CoreContainer.instance = new CoreContainer();
        }
        return CoreContainer.instance;
    };
    CoreContainer.prototype.fetch = function (token) {
        return container.resolve(token);
    };
    CoreContainer.prototype.register = function (key, service) {
        container.register(key, { useValue: service });
    };
    return CoreContainer;
})();
var dependencyRegistry;
var serviceContainer = function () {
    if (!dependencyRegistry) {
        dependencyRegistry = new CoreContainer();
    }
    return dependencyRegistry;
};
export { serviceContainer, CoreContainer };
//# sourceMappingURL=core-container.js.map
