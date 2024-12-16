import EventSystemProviderProps from 'event-system/components/types/event-system-provider-props';
import EventSystemDefinition from 'event-system/deffintions/event-system-definition';
import { EventSystemContext } from 'event-system/event-system-context';
import React from 'react';

import { serviceContainer } from 'service-container/core-container';

export const EventSystemProvider = (
  props: EventSystemProviderProps
): React.ReactNode => {
  const eventSystem =
    serviceContainer().fetch<EventSystemDefinition>('EventSystem');
  return (
    <EventSystemContext.Provider value={eventSystem}>
      {props.children}
    </EventSystemContext.Provider>
  );
};
