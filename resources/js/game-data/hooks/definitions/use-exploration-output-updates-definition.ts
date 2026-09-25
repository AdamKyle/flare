import { ReactNode } from 'react';

export default interface UseExplorationOutputUpdatesDefinition {
  listening: boolean;
  start: () => void;
  renderWire: () => ReactNode;
}
