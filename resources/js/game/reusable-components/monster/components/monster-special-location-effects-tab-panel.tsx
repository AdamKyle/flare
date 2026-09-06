import React, { ReactNode } from 'react';

import MonsterGemEffectContextCard from './monster-gem-effect-context-card';
import MonsterSpecialLocationEffectsTabPanelProps from '../types/monster-special-location-effects-tab-panel-props';

/**
 * The Special Location Effects tab panel: one Card per cached Gem effect
 * context the Monster currently appears in (normal Map, Gem-bearing
 * Location, Map Gem World, Location Gem World).
 */
const MonsterSpecialLocationEffectsTabPanel = ({
  contexts,
  navigation,
}: MonsterSpecialLocationEffectsTabPanelProps): ReactNode => (
  <div className="flex flex-col gap-4">
    {contexts.map((context) => (
      <MonsterGemEffectContextCard
        key={context.key}
        context={context}
        navigation={navigation}
      />
    ))}
  </div>
);

export default MonsterSpecialLocationEffectsTabPanel;
