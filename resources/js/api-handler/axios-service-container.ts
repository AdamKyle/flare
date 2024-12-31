import { ModularContainerDefinition } from 'configuration/deffinitions/modular-container-definition';

import ApiHandler from './api-handler';
import AxiosDefinition from './definitions/axios-definition';
import CoreContainerDefinition from '../service-container/deffinitions/core-container-definition';

export const axiosServiceContainer: ModularContainerDefinition = (
  container: CoreContainerDefinition
) => {
  container.register<AxiosDefinition>('ApiHandler', new ApiHandler());
};
