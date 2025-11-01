import { ReactNode } from 'react';

export default interface UseCharacterUpdateDefinition {
  listening: boolean;
  start: () => void;
  renderWire: () => ReactNode;
}
