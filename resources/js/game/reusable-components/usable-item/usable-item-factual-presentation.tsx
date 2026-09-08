import React, { ReactNode } from 'react';

import UsableItemFactualPresentationProps from './types/usable-item-factual-presentation-props';
import UsableItemEffects from './usable-item-effects';
import { planeTextItemColors } from '../../components/character-sheet/partials/character-inventory/styles/backpack-item-styles';
import ItemMetaSection from '../../components/side-peeks/character-inventory/inventory-item/partials/item-view/item-meta-tsx';

import Separator from 'ui/separator/separator';

const UsableItemFactualPresentation = ({
  item,
  title_class_name: titleClassName,
}: UsableItemFactualPresentationProps): ReactNode => {
  const resolvedTitleClassName =
    titleClassName ??
    planeTextItemColors({
      is_cosmic: false,
      is_mythic: false,
      is_unique: false,
      holy_stacks_applied: 0,
      affix_count: 0,
      type: item.type,
      usable: item.usable,
      holy_level: item.holy_level,
      damages_kingdoms: item.damages_kingdoms,
    });

  return (
    <div className="flex flex-col gap-2 px-4">
      <ItemMetaSection
        name={item.name}
        description={item.description}
        type={item.type}
        titleClassName={resolvedTitleClassName}
      />
      <Separator />
      <UsableItemEffects item={item} />
    </div>
  );
};

export default UsableItemFactualPresentation;
