import {container, InjectionToken} from 'tsyringe';
import GuideQuestListener from "../event-listeners/guide-quest-listener";

class GuideQuestContainer {

    private static instance: GuideQuestContainer;

    public constructor() {
        // Guide Quest Event Listener
        this.register('GuideQuestListenerDefinition', {
            useClass: GuideQuestListener
        })
    }

    /**
     * Get an instance of the container.
     */
    static getInstance() {
        if (!GuideQuestContainer.instance) {
            GuideQuestContainer.instance = new GuideQuestContainer();
        }
        return GuideQuestContainer.instance;
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

let dependencyRegistry: GuideQuestContainer;

const guideQuestServiceContainer = (): GuideQuestContainer => {
    if (!dependencyRegistry) {
        dependencyRegistry = new GuideQuestContainer();
    }

    return dependencyRegistry;
};

export { guideQuestServiceContainer, GuideQuestContainer };




