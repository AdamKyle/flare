
import {container, InjectionToken} from 'tsyringe';
import mainContainer from "./registrations/main-container";
import gameEventContainer from "./registrations/game-event-container";
import {containerRegistry} from "../../configuration/container-registry";

class CoreContainer {

    private static instance: CoreContainer;

    public constructor() {
        mainContainer(this);
        gameEventContainer(this);

        // Game Registrations:
        containerRegistry(this);
    }

    /**
     * Get an instance of the container.
     */
    static getInstance() {
        if (!CoreContainer.instance) {
            CoreContainer.instance = new CoreContainer();
        }
        return CoreContainer.instance;
    }

    /**
     * Fetch dependency
     *
     * Throws is the dependency does not exist.
     *
     * @param key
     */
    public fetch<T>(token: InjectionToken<T>): T {
        return container.resolve<T>(token);
    }

    /**
     * Register a dependency with the container.
     *
     * @param key
     * @param service
     */
    register<T>(key: string, service: T): void {
        container.register(key, { useValue: service });
    }
}

let dependencyRegistry: CoreContainer;

const serviceContainer = (): CoreContainer => {
    if (!dependencyRegistry) {
        dependencyRegistry = new CoreContainer();
    }

    return dependencyRegistry;
};

export { serviceContainer, CoreContainer };
