import React from 'react';

import QuestItemProps from './types/quest-item-props';
import QuestItemFactualPresentation from '../../../../reusable-components/quest-item/quest-item-factual-presentation';
import { planeTextItemColors } from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';

const QuestItem = ({ quest_item }: QuestItemProps) => {
  return (
    <QuestItemFactualPresentation
      item={quest_item}
      title_class_name={planeTextItemColors(quest_item)}
    />
  );
};

export default QuestItem;
