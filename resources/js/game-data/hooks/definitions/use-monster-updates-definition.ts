import { ReactNode } from 'react';

export default interface UseMonsterUpdatesDefinition {
  listening: boolean;
  start: () => void;
  stop: () => void | null;
  renderWire: () => ReactNode;
}
