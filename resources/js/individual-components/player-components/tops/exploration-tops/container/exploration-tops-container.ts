import { container, InjectionToken } from "tsyringe";
import ExplorationTopsAjax from "../ajax/exploration-tops-ajax";
import ExplorationTopsListener from "../event-listeners/exploration-tops-listener";

class ExplorationTopsContainer {
    public constructor() {
        container.register("exploration-tops-ajax", {
            useClass: ExplorationTopsAjax,
        });
        container.register("ExplorationTopsListenerDefinition", {
            useClass: ExplorationTopsListener,
        });
    }

    public fetch<T>(token: InjectionToken<T>): T {
        return container.resolve<T>(token);
    }
}

let dependencyRegistry: ExplorationTopsContainer;

const explorationTopsServiceContainer = (): ExplorationTopsContainer => {
    if (typeof dependencyRegistry === "undefined") {
        dependencyRegistry = new ExplorationTopsContainer();
    }

    return dependencyRegistry;
};

export { explorationTopsServiceContainer, ExplorationTopsContainer };
