import React, { ReactNode } from 'react';

import UsableItemFactualPresentationProps from './types/usable-item-factual-presentation-props';
import UsableItemEffects from './usable-item-effects';
import ItemMetaSection from '../../components/side-peeks/character-inventory/inventory-item/partials/item-view/item-meta-tsx';

import Separator from 'ui/separator/separator';

/**
 * Render the complete permission-neutral factual usable/alchemy Item
 * presentation: identity/description header plus every applicable effect
 * section. Shared by the player-facing inventory usable Item wrapper (which
 * supplies its own rarity-based title styling) and any other read-only
 * context, such as Admin Item detail, that must render the exact same
 * factual content without inventing a second display.
 */
const UsableItemFactualPresentation = ({
  item,
  title_class_name: titleClassName,
}: UsableItemFactualPresentationProps): ReactNode => {
  return (
    <div className="flex flex-col gap-2 px-4">
      <ItemMetaSection
        name={item.name}
        description={item.description}
        type={item.type}
        titleClassName={titleClassName}
      />
      <Separator />
      <UsableItemEffects item={item} />
    </div>
  );
};

export default UsableItemFactualPresentation;
