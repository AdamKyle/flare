import { InjectionToken } from "tsyringe";

export default interface CoreContainerDeffinition {
    /**
     * Fetch the dependencie
     *
     * @param token
     */
    fetch<T>(token: InjectionToken<T>): T;

    /**
     * Register a class to the contianer.
     *
     * @param key
     * @param service
     */
    register<T>(key: string, service: T): void;

    /**
     * Register a singleton class such that when we resolve it, the same instance is always returned.
     *
     * @param key
     * @param service
     */
    registerSingleton<T>(
        key: string,
        service: { new (...args: unknown[]): T },
    ): void;
}
