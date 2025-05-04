import React from 'react';
import { ReactNode } from 'react';

import EchoHandlerProviderProps from './types/echo-handler-provider-props';
import EchoInitializerDefinition from '../definitions/echo-initializer-definition';
import { EchoHandlerContext } from '../echo-handler-context';

import { serviceContainer } from 'service-container/core-container';

export const EchoHandlerProvider = (
  props: EchoHandlerProviderProps
): ReactNode => {
  const echoInitialization =
    serviceContainer().fetch<EchoInitializerDefinition>('EchoInitialization');

  return (
    <EchoHandlerContext.Provider value={{ echoInitialization }}>
      {props.children}
    </EchoHandlerContext.Provider>
  );
};
