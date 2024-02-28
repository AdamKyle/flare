import ShopAjax from "../ajax/shop-ajax";

import {container, InjectionToken} from 'tsyringe';
import ShopTableColumns from "../shop-table/colums/shop-table-columns";
import ShopListener from "../event-listeners/shop-listener";

class ShopContainer {

    private static instance: ShopContainer;

    public constructor() {
        this.register('shop-ajax', {
            useClass: ShopAjax
        });

        this.register('shop-table-columns', {
            useClass: ShopTableColumns
        });

        // Shop Event Listener
        this.register('ShopListenerDefinition', {
            useClass: ShopListener
        })
    }

    /**
     * Get an instance of the container.
     */
    static getInstance() {
        if (!ShopContainer.instance) {
            ShopContainer.instance = new ShopContainer();
        }
        return ShopContainer.instance;
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

let dependencyRegistry: ShopContainer;

const shopServiceContainer = (): ShopContainer => {
    if (!dependencyRegistry) {
        dependencyRegistry = new ShopContainer();
    }

    return dependencyRegistry;
};

export { shopServiceContainer, ShopContainer };




