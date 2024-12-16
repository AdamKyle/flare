import CoreContainerDefinition from '../../service-container/deffinitions/core-container-definition';

export type ModularContainerDeffintion = (
  container: CoreContainerDefinition
) => void;
