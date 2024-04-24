import { container, InjectionToken } from "tsyringe";
import ItemTableAjax from "../ajax/item-table-ajax";
import ItemTableColumns from "../columns/item-table-columns";

class ItemsContainer {
    private static instance: ItemsContainer;

    public constructor() {
        this.register("items-ajax", {
            useClass: ItemTableAjax,
        });

        this.register("items-table-columns", {
            useClass: ItemTableColumns,
        });
    }

    /**
     * Get an instance of the container.
     */
    static getInstance() {
        if (!ItemsContainer.instance) {
            ItemsContainer.instance = new ItemsContainer();
        }
        return ItemsContainer.instance;
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

let dependencyRegistry: ItemsContainer;

const itemsTableServiceContainer = (): ItemsContainer => {
    if (!dependencyRegistry) {
        dependencyRegistry = new ItemsContainer();
    }

    return dependencyRegistry;
};

export { itemsTableServiceContainer, ItemsContainer };
