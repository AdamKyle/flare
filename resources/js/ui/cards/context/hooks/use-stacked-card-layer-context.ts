import { useContext } from 'react';

import StackedCardLayerContextDefinition from '../definitions/stacked-card-layer-context-definition';
import StackedCardLayerContext from '../stacked-card-layer-context';

/**
 * Read the nullable current `StackedCardLayerContext`. Never throws merely
 * because no host exists; callers fall back to local presentation.
 *
 * @return  The current nullable layer-host context.
 */
export const useStackedCardLayerContext =
  (): StackedCardLayerContextDefinition | null =>
    useContext(StackedCardLayerContext);
