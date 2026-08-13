import React from 'react';

import CelestialDetailsPanelProps from './types/celestial-details-panel-props';
import MonsterStatDetails from '../../../../actions/partials/monster-stat-section/monster-stat-details';

import StackedCard from 'ui/cards/stacked-card';

const CelestialDetailsPanel = ({
  monster,
  on_close,
}: CelestialDetailsPanelProps) => {
  return (
    <StackedCard on_close={on_close} aria_label={`${monster.name} details`}>
      <h2 className="mb-4 text-lg font-semibold text-gray-900 dark:text-gray-100">
        {monster.name}
      </h2>
      <MonsterStatDetails monster={monster} single_column={true} />
    </StackedCard>
  );
};

export default CelestialDetailsPanel;
