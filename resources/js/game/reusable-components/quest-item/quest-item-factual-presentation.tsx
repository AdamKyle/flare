import React, { ReactNode } from 'react';

import QuestItemDetails from './quest-item-details';
import QuestItemFactualPresentationProps from './types/quest-item-factual-presentation-props';
import { planeTextItemColors } from '../../components/character-sheet/partials/character-inventory/styles/backpack-item-styles';
import ItemMetaSection from '../../components/side-peeks/character-inventory/inventory-item/partials/item-view/item-meta-tsx';

import Separator from 'ui/separator/separator';

const QuestItemFactualPresentation = ({
  item,
  title_class_name: titleClassName,
  navigation,
}: QuestItemFactualPresentationProps): ReactNode => {
  const resolvedTitleClassName =
    titleClassName ??
    planeTextItemColors({
      is_cosmic: false,
      is_mythic: false,
      is_unique: false,
      holy_stacks_applied: 0,
      affix_count: 0,
      type: item.type,
      usable: false,
      holy_level: null,
      damages_kingdoms: false,
    });

  return (
    <div className="flex flex-col gap-2 px-4">
      <ItemMetaSection
        name={item.name}
        description={item.description}
        type={item.type}
        effect={item.effect ?? undefined}
        titleClassName={resolvedTitleClassName}
      />
      <Separator />
      <QuestItemDetails item={item} navigation={navigation} />
    </div>
  );
};

export default QuestItemFactualPresentation;
