import { container, InjectionToken } from "tsyringe";
import UserLoginDuration from "../ajax/user-login-duration";

class CharactersOnlineContainer {
    private static instance: CharactersOnlineContainer;

    public constructor() {
        this.register("user-login-duration-ajax", {
            useClass: UserLoginDuration,
        });
    }

    /**
     * Get an instance of the container.
     */
    static getInstance() {
        if (!CharactersOnlineContainer.instance) {
            CharactersOnlineContainer.instance =
                new CharactersOnlineContainer();
        }
        return CharactersOnlineContainer.instance;
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

let dependencyRegistry: CharactersOnlineContainer;

const charactersOnlineContainer = (): CharactersOnlineContainer => {
    if (!dependencyRegistry) {
        dependencyRegistry = new CharactersOnlineContainer();
    }

    return dependencyRegistry;
};

export { charactersOnlineContainer, CharactersOnlineContainer };
