import { container, InjectionToken } from "tsyringe";
import DelveTopsAjax from "../ajax/delve-tops-ajax";
import DelveTopsListener from "../event-listeners/delve-tops-listener";

class DelveTopsContainer {
    public constructor() {
        container.register("delve-tops-ajax", {
            useClass: DelveTopsAjax,
        });
        container.register("DelveTopsListenerDefinition", {
            useClass: DelveTopsListener,
        });
    }

    public fetch<T>(token: InjectionToken<T>): T {
        return container.resolve<T>(token);
    }
}

let dependencyRegistry: DelveTopsContainer;

const delveTopsServiceContainer = (): DelveTopsContainer => {
    if (typeof dependencyRegistry === "undefined") {
        dependencyRegistry = new DelveTopsContainer();
    }

    return dependencyRegistry;
};

export { delveTopsServiceContainer, DelveTopsContainer };
