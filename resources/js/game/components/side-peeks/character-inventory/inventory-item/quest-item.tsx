import React from 'react';

import QuestItemProps from './types/quest-item-props';
import QuestItemFactualPresentation from '../../../../reusable-components/quest-item/quest-item-factual-presentation';
import { planeTextItemColors } from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';

const QuestItem = ({ quest_item: questItem, navigation }: QuestItemProps) => {
  return (
    <QuestItemFactualPresentation
      item={questItem}
      title_class_name={planeTextItemColors(questItem)}
      navigation={navigation}
    />
  );
};

export default QuestItem;
