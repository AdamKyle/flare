import { useEventSystem } from 'event-system/hooks/use-event-system';

import AxiosErrorMessageDefinition from '../definitions/use-axios-error-message-handler-definition';
import { AxiosEventTypes } from '../event-types/axios-events-types';

export const useAxiosErrorMessageHandler = (): AxiosErrorMessageDefinition => {
  const eventSystem = useEventSystem();

  const manageAxiosErrorEmitter = eventSystem.isEventRegistered(
    AxiosEventTypes.ERROR
  )
    ? eventSystem.getEventEmitter<{ [key: string]: string }>(
        AxiosEventTypes.ERROR
      )
    : eventSystem.registerEvent<{ [key: string]: string }>(
        AxiosEventTypes.ERROR
      );

  const onError = (message: string) => {
    manageAxiosErrorEmitter.emit(AxiosEventTypes.ERROR, message);
  };

  return {
    onError,
  };
};
