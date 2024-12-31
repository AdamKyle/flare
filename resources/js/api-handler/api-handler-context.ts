import { createContext } from 'react';

import ApiHandleContextDefinition from './definitions/api-handle-context-definition';

export const ApiHandlerContext =
  createContext<ApiHandleContextDefinition | null>(null);
