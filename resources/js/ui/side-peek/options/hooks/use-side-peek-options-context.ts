import { useContext } from 'react';

import SidePeekOptionsContext from 'ui/side-peek/options/side-peek-options-context';
import SidePeekOptionsContextDefinition from 'ui/side-peek/options/types/side-peek-options-context-definition';

/**
 * Read the nullable current `SidePeekOptionsContext`. Never throws merely
 * because no host exists; callers fall back to rendering no footer.
 *
 * @return  The current nullable side-peek options context.
 */
export const useSidePeekOptionsContext =
  (): SidePeekOptionsContextDefinition | null =>
    useContext(SidePeekOptionsContext);
