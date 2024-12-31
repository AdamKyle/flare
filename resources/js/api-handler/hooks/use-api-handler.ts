import { useContext } from 'react';

import { ApiHandlerContext } from '../api-handler-context';
import ApiHandleContextDefinition from '../definitions/api-handle-context-definition';

export const useApiHandler = (): ApiHandleContextDefinition => {
  const context = useContext(ApiHandlerContext);

  if (!context) {
    throw new Error('useApiHandler must be used within an ApiHandlerContext');
  }

  return context;
};
