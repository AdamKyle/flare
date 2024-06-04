import { container, InjectionToken } from "tsyringe";
import MouseHandlers from "../grid/mouse-handlers";
import ToolTipHandler from "../grid/tool-tip-handler";
import InitializeMapAjax from "../ajax/initialize-map-ajax";

class GridOverlayContainer {
    private static instance: GridOverlayContainer;

    public constructor() {
        this.register("mouse-handlers", {
            useClass: MouseHandlers,
        });

        this.register("tool-tip-handler", {
            useClass: ToolTipHandler,
        });

        this.register("ajax-interface", {
            useClass: InitializeMapAjax,
        });
    }

    /**
     * Get an instance of the container.
     */
    static getInstance() {
        if (!GridOverlayContainer.instance) {
            GridOverlayContainer.instance = new GridOverlayContainer();
        }
        return GridOverlayContainer.instance;
    }

    /**
     * Fetch dependency
     *
     * Throws is the dependency does not exist.
     *
     * @param token
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

let dependencyRegistry: GridOverlayContainer;

const gridOverLayContainer = (): GridOverlayContainer => {
    if (!dependencyRegistry) {
        dependencyRegistry = new GridOverlayContainer();
    }

    return dependencyRegistry;
};

export { gridOverLayContainer, GridOverlayContainer };
