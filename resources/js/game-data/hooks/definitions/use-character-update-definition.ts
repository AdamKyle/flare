import { ReactNode } from 'react';

export default interface UseCharterUpdateStreamResponse {
  listening: boolean;
  start: () => void;
  renderWire: () => ReactNode;
}
