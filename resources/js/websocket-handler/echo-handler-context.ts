import { createContext } from 'react';

import EchoContextDefinition from './definitions/echo-context-definition';

export const EchoHandlerContext = createContext<EchoContextDefinition | null>(
  null
);
