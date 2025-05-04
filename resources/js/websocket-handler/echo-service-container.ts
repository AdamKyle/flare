import { ModularContainerDefinition } from 'configuration/deffinitions/modular-container-definition';

import EchoInitializerDefinition from './definitions/echo-initializer-definition';
import EchoInitializer from './echo-initializer';

import CoreContainerDefinition from 'service-container/deffinitions/core-container-definition';

export const echoServiceContainer: ModularContainerDefinition = (
  container: CoreContainerDefinition
) => {
  container.register<EchoInitializerDefinition>(
    'EchoInitializer',
    new EchoInitializer()
  );
};
