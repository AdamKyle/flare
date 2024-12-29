import { createContext } from 'react';

import AxiosDefinition from './definitions/axios-definition';

export const ApiHandlerContext = createContext<AxiosDefinition | null>(null);
