import { container, InjectionToken } from "tsyringe";
import TopsAjax from "../ajax/tops-ajax";
import TopsListener from "../event-listeners/tops-listener";

class TopsContainer {
    private static instance: TopsContainer;

    public constructor() {
        this.register("tops-ajax", { useClass: TopsAjax });
        this.register("TopsListenerDefinition", { useClass: TopsListener });
    }

    static getInstance() {
        if (!TopsContainer.instance) {
            TopsContainer.instance = new TopsContainer();
        }

        return TopsContainer.instance;
    }

    public fetch<T>(token: InjectionToken<T>): T {
        return container.resolve<T>(token);
    }

    register<T>(key: string, service: T): void {
        container.register(key, { useValue: service });
    }
}

let dependencyRegistry: TopsContainer;

const topsServiceContainer = (): TopsContainer => {
    if (typeof dependencyRegistry === "undefined") {
        dependencyRegistry = new TopsContainer();
    }

    return dependencyRegistry;
};

export { topsServiceContainer, TopsContainer };
