import EventSystemDefinition from 'event-system/deffintions/event-system-definition';
import { createContext } from 'react';

export const EventSystemContext = createContext<EventSystemDefinition | null>(
  null
);
