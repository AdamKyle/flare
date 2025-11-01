import { ReactNode } from 'react';

export default interface UseMonsterUpdateDefinition {
  listening: boolean;
  start: () => void;
  renderWire: () => ReactNode;
}
