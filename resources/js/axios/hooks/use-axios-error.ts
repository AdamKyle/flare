import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import AxiosErrorMessageDefinition from '../definitions/use-axios-error-definition';
import { AxiosEventTypes } from '../event-types/axios-events-types';
import UseAxiosErrorState from '../types/use-axios-error-state';

export const useAxiosError = (): AxiosErrorMessageDefinition => {
  const eventSystem = useEventSystem();

  const [errorMessage, setErrorMessage] =
    useState<UseAxiosErrorState['errorMessage']>('');

  const manageAxiosErrorEmitter = eventSystem.getEventEmitter<{
    [key: string]: string;
  }>(AxiosEventTypes.ERROR);

  useEffect(() => {
    const updateMessage = (message: string) => {
      setErrorMessage(message);
    };

    manageAxiosErrorEmitter.on(AxiosEventTypes.ERROR, updateMessage);

    return () => {
      manageAxiosErrorEmitter.off(AxiosEventTypes.ERROR, updateMessage);
    };
  }, [manageAxiosErrorEmitter]);

  return {
    errorMessage,
  };
};
