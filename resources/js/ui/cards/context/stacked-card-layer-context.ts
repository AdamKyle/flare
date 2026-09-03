import { createContext } from 'react';

import StackedCardLayerContextDefinition from './definitions/stacked-card-layer-context-definition';

/**
 * Nullable because a `StackedCard` can still exist outside a `SidePeek` (or
 * outside any other `StackedCard`), in which case it falls back to its
 * local, non-portaled presentation.
 */
const StackedCardLayerContext =
  createContext<StackedCardLayerContextDefinition | null>(null);

export default StackedCardLayerContext;
