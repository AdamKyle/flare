import React, { ReactNode } from 'react';

import AdminItemPresentationProps from './types/admin-item-presentation-props';
import ItemDetails from '../../../game/components/side-peeks/item-details/item-details';
import QuestItemFactualPresentation from '../../../game/reusable-components/quest-item/quest-item-factual-presentation';
import UsableItemFactualPresentation from '../../../game/reusable-components/usable-item/usable-item-factual-presentation';

/**
 * Delegates to the existing authoritative 2.0 Item presentation for the
 * resolved kind. Equippable Items reuse the existing self-fetching
 * `ItemDetails` side-peek content directly. Quest and usable/alchemy Items
 * reuse the same permission-neutral factual presentation components the
 * player-facing inventory renders, backed by the same canonical transformers,
 * so Admin never reproduces those factual fields independently.
 */
const AdminItemPresentation = ({
  item,
  navigation,
}: AdminItemPresentationProps): ReactNode => {
  if (item.presentation_kind === 'equippable') {
    return <ItemDetails item_id={item.id} is_open title={item.name} />;
  }

  if (item.presentation_kind === 'quest') {
    return (
      <QuestItemFactualPresentation
        item={item.presentation}
        navigation={navigation}
      />
    );
  }

  return <UsableItemFactualPresentation item={item.presentation} />;
};

export default AdminItemPresentation;
