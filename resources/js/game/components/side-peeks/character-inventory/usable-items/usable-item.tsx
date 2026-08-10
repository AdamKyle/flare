import React from 'react';

import UsableItemProps from './types/usable-item-props';
import UsableItemEffects from '../../../../reusable-components/usable-item/usable-item-effects';
import { planeTextItemColors } from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import ItemMetaSection from '../inventory-item/partials/item-view/item-meta-tsx';

import Separator from 'ui/separator/separator';

const UsableItem = ({ item }: UsableItemProps) => {
  return (
    <>
      <div className="flex flex-col gap-2 px-4">
        <ItemMetaSection
          name={item.name}
          description={item.description}
          type={item.type}
          titleClassName={planeTextItemColors(item)}
        />
        <Separator />
        <UsableItemEffects item={item} />
      </div>
    </>
  );
};

export default UsableItem;
