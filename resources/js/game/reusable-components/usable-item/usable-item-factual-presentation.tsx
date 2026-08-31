import React, { ReactNode } from 'react';

import UsableItemFactualPresentationProps from './types/usable-item-factual-presentation-props';
import UsableItemEffects from './usable-item-effects';
import { planeTextItemColors } from '../../components/character-sheet/partials/character-inventory/styles/backpack-item-styles';
import ItemMetaSection from '../../components/side-peeks/character-inventory/inventory-item/partials/item-view/item-meta-tsx';

import Separator from 'ui/separator/separator';

/**
 * Render the complete permission-neutral factual usable/alchemy Item
 * presentation: identity/description header plus every applicable effect
 * section. Shared by the player-facing inventory usable Item wrapper (which
 * supplies its own rarity-based title styling) and any other read-only
 * context, such as Admin Item detail, that must render the exact same
 * factual content without inventing a second display.
 *
 * Callers that do not already own a rarity-aware title color (Admin
 * contexts) get the canonical Item color by default, rather than an
 * unstyled title falling back to browser-default black text.
 */
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
