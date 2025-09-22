import React from 'react';

import DefinitionRow from '../../viewable-sections/definition-row';
import InfoLabel from '../../viewable-sections/info-label';
import QuestMapRowProps from '../types/partials/quest-map-row-props';

const QuestMapRow = ({ map }: QuestMapRowProps) => {
  if (map == null) {
    return null;
  }

  return (
    <DefinitionRow
      left={<InfoLabel label="While On Map" />}
      right={<span className="text-gray-800 dark:text-gray-200">{map}</span>}
    />
  );
};

export default QuestMapRow;
