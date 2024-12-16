import EventSystemDefinition from 'event-system/deffintions/event-system-definition';
import { EventSystemContext } from 'event-system/event-system-context';
import { useContext } from 'react';

export const useEventSystem = (): EventSystemDefinition => {
  const context = useContext(EventSystemContext);

  if (!context) {
    throw new Error(
      'useEventSystem must be used within an EventSystemProvider'
    );
  }

  return context;
};
