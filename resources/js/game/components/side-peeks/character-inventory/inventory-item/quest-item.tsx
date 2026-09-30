import React from 'react';

import QuestItemProps from './types/quest-item-props';
import QuestItemFactualPresentation from '../../../../reusable-components/quest-item/quest-item-factual-presentation';

const QuestItem = ({ quest_item: questItem, navigation }: QuestItemProps) => {
  return (
    <QuestItemFactualPresentation item={questItem} navigation={navigation} />
  );
};

export default QuestItem;
