import { container, InjectionToken } from "tsyringe";

import CoreContainerDeffinition from "./deffinitions/core-container-definition";
import { serviceContainers } from "../configuration/modular-container";

export default class CoreContainer implements CoreContainerDeffinition {
    private static instance: CoreContainer;

    constructor() {
        this.autoRegisterModules();
    }

    /**
     * Returns the instance of the container.
     *
     * @returns CoreContainer
     */
    static getInstance(): CoreContainer {
        if (!CoreContainer.instance) {
            CoreContainer.instance = new CoreContainer();
        }

        return CoreContainer.instance;
    }

    /**
     * Fetch from the container.
     *
     * @param token
     * @returns
     */
    public fetch<T>(token: InjectionToken<T>): T {
        return container.resolve<T>(token);
    }

    /**
     * Register a dependency to the container.
     *
     * @param key
     * @param service
     */
    public register<T>(key: string, service: T): void {
        container.register(key, { useValue: service });
    }

    /**
     * Register a singleton to the container.
     *
     * @param key
     * @param service
     */
    public registerSingleton<T>(
        key: string,
        service: { new (...args: unknown[]): T },
    ): void {
        container.registerSingleton(key, service);
    }

    /**
     * auto register the modules.
     */
    private autoRegisterModules(): void {
        serviceContainers.forEach((registerModule) => registerModule(this));
    }
}

let dependencyRegistry: CoreContainer;

const serviceContainer = (): CoreContainer => {
    if (!dependencyRegistry) {
        dependencyRegistry = CoreContainer.getInstance();
    }

    return dependencyRegistry;
};

export { serviceContainer, CoreContainer };
