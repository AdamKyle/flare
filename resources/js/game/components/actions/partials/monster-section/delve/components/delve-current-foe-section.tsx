import React, { ReactNode } from 'react';

import DelveCurrentFoeSectionProps from './types/delve-current-foe-section-props';
import ExplorationDetailSection from '../../exploration/components/exploration-detail-section';
import DelveFoeStatRowDefinition from '../types/delve-foe-stat-row-definition';
import { buildDelveFoeStatRows } from '../utils/build-delve-foe-stat-rows';

import Dd from 'ui/dl/dd';
import Dl from 'ui/dl/dl';
import Dt from 'ui/dl/dt';

const DelveCurrentFoeSection = ({
  current_foe: currentFoe,
}: DelveCurrentFoeSectionProps): ReactNode => {
  const statRows = buildDelveFoeStatRows(currentFoe.stats);

  const renderStatRow = (statRow: DelveFoeStatRowDefinition) => (
    <React.Fragment key={statRow.key}>
      <Dt>{statRow.label}</Dt>
      <Dd>{statRow.value}</Dd>
    </React.Fragment>
  );

  const renderStats = (): ReactNode => {
    if (!currentFoe.stats_available || statRows.length === 0) {
      return null;
    }

    return <Dl>{statRows.map(renderStatRow)}</Dl>;
  };

  return (
    <ExplorationDetailSection title="Current Foe">
      <Dl>
        <Dt>Name</Dt>
        <Dd>{currentFoe.name ?? 'Unknown'}</Dd>
        <Dt>Pack Size</Dt>
        <Dd>{currentFoe.pack_size}</Dd>
        <Dt>Enemy Strength Boost</Dt>
        <Dd>{currentFoe.enemy_strength_boost}</Dd>
      </Dl>
      <p className="text-sm text-gray-700 dark:text-gray-300">
        {currentFoe.message}
      </p>
      {renderStats()}
    </ExplorationDetailSection>
  );
};

export default DelveCurrentFoeSection;
