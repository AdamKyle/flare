import { ReactNode } from 'react';

export default interface UseAnnouncementsUpdateDefinition {
  listening: boolean;
  start: () => void;
  renderWire: () => ReactNode;
}
