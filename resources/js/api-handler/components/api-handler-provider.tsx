import React, { ReactNode } from 'react';

import { ApiHandlerContext } from '../api-handler-context';
import ApiHandlerProviderProps from './types/api-handler-provider-props';
import AxiosDefinition from '../definitions/axios-definition';
import { getUrl } from '../utils/get-url';

import { serviceContainer } from 'service-container/core-container';

export const ApiHandlerProvider = (
  props: ApiHandlerProviderProps
): ReactNode => {
  const apiHandler = serviceContainer().fetch<AxiosDefinition>('ApiHandler');

  return (
    <ApiHandlerContext.Provider value={{ apiHandler, getUrl }}>
      {props.children}
    </ApiHandlerContext.Provider>
  );
};
