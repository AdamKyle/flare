import React from 'react';

import UsableItemProps from './types/usable-item-props';
import UsableItemFactualPresentation from '../../../../reusable-components/usable-item/usable-item-factual-presentation';
import { planeTextItemColors } from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';

const UsableItem = ({ item }: UsableItemProps) => {
  return (
    <UsableItemFactualPresentation
      item={item}
      title_class_name={planeTextItemColors(item)}
    />
  );
};

export default UsableItem;
