import { useEventSystem } from 'event-system/hooks/use-event-system';

import UseAxiosSuccessHandlerDefinition from '../definitions/use-axios-success-handler-definition';
import { AxiosEventTypes } from '../event-types/axios-events-types';

export const useAxiosSuccessHandler = <
  T,
>(): UseAxiosSuccessHandlerDefinition<T> => {
  const eventSystem = useEventSystem();

  const manageAxiosSuccessEmitter = eventSystem.isEventRegistered(
    AxiosEventTypes.SUCCESS
  )
    ? eventSystem.getEventEmitter<{ [key: string]: T }>(AxiosEventTypes.SUCCESS)
    : eventSystem.registerEvent<{ [key: string]: T }>(AxiosEventTypes.SUCCESS);

  const onSuccess = (data: T): void => {
    manageAxiosSuccessEmitter.emit(AxiosEventTypes.SUCCESS, data);
  };

  return {
    onSuccess,
  };
};
