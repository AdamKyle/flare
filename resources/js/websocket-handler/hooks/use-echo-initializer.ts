import { useContext } from 'react';

import { EchoHandlerContext } from '../echo-handler-context';

export const useEchoInitializer = () => {
  const context = useContext(EchoHandlerContext);

  if (!context) {
    throw new Error(
      'useEchoInitializer must be used within an EchoHandlerContext'
    );
  }

  return context;
};
