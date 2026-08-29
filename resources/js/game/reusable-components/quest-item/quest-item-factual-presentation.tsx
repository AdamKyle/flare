import React, { ReactNode } from 'react';

import QuestItemDetails from './quest-item-details';
import QuestItemFactualPresentationProps from './types/quest-item-factual-presentation-props';
import ItemMetaSection from '../../components/side-peeks/character-inventory/inventory-item/partials/item-view/item-meta-tsx';

import Separator from 'ui/separator/separator';

/**
 * Render the complete permission-neutral factual Quest Item presentation:
 * identity/description/effect header plus every relationship section.
 * Shared by the player-facing inventory Quest Item wrapper (which supplies
 * its own rarity-based title styling) and any other read-only context, such
 * as Admin Item detail and Location quest-item drops, that must render the
 * exact same factual content without inventing a second display.
 */
const QuestItemFactualPresentation = ({
  item,
  title_class_name: titleClassName,
}: QuestItemFactualPresentationProps): ReactNode => {
  return (
    <div className="flex flex-col gap-2 px-4">
      <ItemMetaSection
        name={item.name}
        description={item.description}
        type={item.type}
        effect={item.effect ?? undefined}
        titleClassName={titleClassName}
      />
      <Separator />
      <QuestItemDetails item={item} />
    </div>
  );
};

export default QuestItemFactualPresentation;
