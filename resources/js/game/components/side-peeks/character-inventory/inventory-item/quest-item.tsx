import React from 'react';

import ItemMetaSection from './partials/item-view/item-meta-tsx';
import QuestItemProps from './types/quest-item-props';
import QuestItemDetails from '../../../../reusable-components/quest-item/quest-item-details';
import { planeTextItemColors } from '../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';

import Separator from 'ui/separator/separator';

const QuestItem = ({ quest_item }: QuestItemProps) => {
  return (
    <>
      <div className="px-4 flex flex-col gap-2">
        <ItemMetaSection
          name={quest_item.name}
          description={quest_item.description}
          type={quest_item.type}
          effect={quest_item.effect}
          titleClassName={planeTextItemColors(quest_item)}
        />
        <Separator />
        <QuestItemDetails item={quest_item} />
      </div>
    </>
  );
};

export default QuestItem;
