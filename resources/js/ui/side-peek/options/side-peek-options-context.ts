import { createContext } from 'react';

import SidePeekOptionsContextDefinition from 'ui/side-peek/options/types/side-peek-options-context-definition';

const SidePeekOptionsContext =
  createContext<SidePeekOptionsContextDefinition | null>(null);

export default SidePeekOptionsContext;
