
import {container} from 'tsyringe';

class CoreContainer {

    private static instance: CoreContainer;

    private constructor() {

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
     * @throws Error
     */
    public fetch<T>(key: string): T {
        const service = container.resolve<T>(key);

        if (!service) {
            throw new Error(`Service not found: ${key}`);
        }

        return service;
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
