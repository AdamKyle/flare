import { container, InjectionToken } from "tsyringe";
import FactionLoyaltyTopsAjax from "../ajax/faction-loyalty-tops-ajax";
import FactionLoyaltyTopsListener from "../event-listeners/faction-loyalty-tops-listener";

class FactionLoyaltyTopsContainer {
    public constructor() {
        container.register("faction-loyalty-tops-ajax", {
            useClass: FactionLoyaltyTopsAjax,
        });
        container.register("FactionLoyaltyTopsListenerDefinition", {
            useClass: FactionLoyaltyTopsListener,
        });
    }

    public fetch<T>(token: InjectionToken<T>): T {
        return container.resolve<T>(token);
    }
}

let dependencyRegistry: FactionLoyaltyTopsContainer;

const factionLoyaltyTopsServiceContainer = (): FactionLoyaltyTopsContainer => {
    if (typeof dependencyRegistry === "undefined") {
        dependencyRegistry = new FactionLoyaltyTopsContainer();
    }

    return dependencyRegistry;
};

export { factionLoyaltyTopsServiceContainer, FactionLoyaltyTopsContainer };
