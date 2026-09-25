import { ReactNode } from 'react';

export default interface UseBattleRewardProgressionUpdatesDefinition {
  listening: boolean;
  start: () => void;
  renderWire: () => ReactNode;
}
