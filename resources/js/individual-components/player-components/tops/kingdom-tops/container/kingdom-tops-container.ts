import { container, InjectionToken } from "tsyringe";
import KingdomTopsAjax from "../ajax/kingdom-tops-ajax";
import KingdomTopsListener from "../event-listeners/kingdom-tops-listener";

class KingdomTopsContainer {
    public constructor() {
        container.register("kingdom-tops-ajax", {
            useClass: KingdomTopsAjax,
        });
        container.register("KingdomTopsListenerDefinition", {
            useClass: KingdomTopsListener,
        });
    }

    public fetch<T>(token: InjectionToken<T>): T {
        return container.resolve<T>(token);
    }
}

let dependencyRegistry: KingdomTopsContainer;

const kingdomTopsServiceContainer = (): KingdomTopsContainer => {
    if (typeof dependencyRegistry === "undefined") {
        dependencyRegistry = new KingdomTopsContainer();
    }

    return dependencyRegistry;
};

export { kingdomTopsServiceContainer, KingdomTopsContainer };
