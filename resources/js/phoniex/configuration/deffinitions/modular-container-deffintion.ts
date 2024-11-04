import CoreContainerDeffinition from "../../service-container/deffinitions/core-container-definition";

export type ModularContainerDeffintion = (
    container: CoreContainerDeffinition,
) => void;
