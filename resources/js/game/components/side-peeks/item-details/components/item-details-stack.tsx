import React, { ReactNode } from 'react';

import ItemDetailsBody from './item-details-body';
import ItemDetailsStackProps from './types/item-details-stack-props';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';

const ItemDetailsStack = ({
  item_id: itemId,
  on_close: onClose,
}: ItemDetailsStackProps): ReactNode => {
  return (
    <StackedCard
      on_close={onClose}
      aria_label="Item Details"
      content_mode={StackedCardContentMode.FULL_BLEED}
    >
      <div className="min-h-0 flex-1 overflow-y-auto py-4">
        <ItemDetailsBody item_id={itemId} />
      </div>
    </StackedCard>
  );
};

export default ItemDetailsStack;
