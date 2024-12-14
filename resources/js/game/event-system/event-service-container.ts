import { ModularContainerDeffintion } from "../configuration/deffinitions/modular-container-deffintion";
import CoreContainerDeffinition from "../service-container/deffinitions/core-container-definition";
import EventSystemDeffintion from "./deffintions/event-system-deffintion";
import EventSystem from "./event-system";

export const eventServiceContainer: ModularContainerDeffintion = (
    container: CoreContainerDeffinition,
) => {
    container.registerSingleton<EventSystemDeffintion>(
        "EventSystem",
        EventSystem,
    );
};
