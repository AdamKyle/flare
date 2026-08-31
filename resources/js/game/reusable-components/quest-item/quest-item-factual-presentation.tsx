import React, { ReactNode } from 'react';

import QuestItemDetails from './quest-item-details';
import QuestItemFactualPresentationProps from './types/quest-item-factual-presentation-props';
import { planeTextItemColors } from '../../components/character-sheet/partials/character-inventory/styles/backpack-item-styles';
import ItemMetaSection from '../../components/side-peeks/character-inventory/inventory-item/partials/item-view/item-meta-tsx';

import Separator from 'ui/separator/separator';

/**
 * Render the complete permission-neutral factual Quest Item presentation:
 * identity/description/effect header plus every relationship section.
 * Shared by the player-facing inventory Quest Item wrapper (which supplies
 * its own rarity-based title styling) and any other read-only context, such
 * as Admin Item detail and Location quest-item drops, that must render the
 * exact same factual content without inventing a second display.
 *
 * Callers that do not already own a rarity-aware title color (Admin/Location
 * contexts) get the canonical Quest Item color by default, rather than an
 * unstyled title falling back to browser-default black text.
 */
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
