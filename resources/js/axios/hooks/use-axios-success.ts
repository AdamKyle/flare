import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import UseAxiosSuccessDefinition from '../definitions/use-axios-success-definition';
import { AxiosEventTypes } from '../event-types/axios-events-types';
import UseAxiosSuccessState from '../types/use-axios-success-state';

export const useAxiosError = <T>(): UseAxiosSuccessDefinition<T> => {
  const eventSystem = useEventSystem();

  const [data, setData] = useState<UseAxiosSuccessState<T>['data']>(null);

  const manageAxiosErrorEmitter = eventSystem.getEventEmitter<{
    [key: string]: T;
  }>(AxiosEventTypes.SUCCESS);

  useEffect(() => {
    const updateData = (data: T) => {
      setData(data);
    };

    manageAxiosErrorEmitter.on(AxiosEventTypes.SUCCESS, updateData);

    return () => {
      manageAxiosErrorEmitter.off(AxiosEventTypes.SUCCESS, updateData);
    };
  }, [manageAxiosErrorEmitter]);

  return {
    data,
  };
};
