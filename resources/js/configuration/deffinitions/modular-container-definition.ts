import CoreContainerDefinition from '../../service-container/deffinitions/core-container-definition';

export type ModularContainerDefinition = (
  container: CoreContainerDefinition
) => void;
