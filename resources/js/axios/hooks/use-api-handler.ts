import { useContext } from 'react';

import { ApiHandlerContext } from '../api-handler-context';
import AxiosDefinition from '../definitions/axios-definition';

export const useApiHandler = (): AxiosDefinition => {
  const context = useContext(ApiHandlerContext);

  if (!context) {
    throw new Error('useApiHandler must be used within an ApiHandlerContext');
  }

  return context;
};
