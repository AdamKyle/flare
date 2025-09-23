import React from 'react';

import QuestMapRow from './quest-map-row';
import DefinitionRow from '../../viewable-sections/definition-row';
import InfoLabel from '../../viewable-sections/info-label';
import TextRow from '../../viewable-sections/text-row';
import QuestRowsProps from '../types/partials/quest-rows-props';

const QuestRows = ({ heading, name, npc, map }: QuestRowsProps) => {
  return (
    <>
      <DefinitionRow
        left={<InfoLabel label={heading} />}
        right={<span className="text-gray-800 dark:text-gray-200">{name}</span>}
      />
      <TextRow label="For NPC" value={npc} />
      <QuestMapRow map={map} />
    </>
  );
};

export default QuestRows;
