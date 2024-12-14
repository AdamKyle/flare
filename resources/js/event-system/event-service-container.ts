import EventSystemDefinition from "./deffintions/event-system-definition";
import EventSystem from "./event-system";
import { ModularContainerDeffintion } from "../configuration/deffinitions/modular-container-deffintion";
import CoreContainerDeffinition from "../service-container/deffinitions/core-container-definition";

export const eventServiceContainer: ModularContainerDeffintion = (
    container: CoreContainerDeffinition,
) => {
    container.registerSingleton<EventSystemDefinition>(
        "EventSystem",
        EventSystem,
    );
};
