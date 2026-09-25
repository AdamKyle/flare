import React, { ReactNode } from 'react';

import InventoryItem from './inventory-item';
import InventoryItemDetailStackProps from './types/inventory-item-detail-stack-props';
import InventoryStackBody from '../components/inventory-stack-body';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';

const InventoryItemDetailStack = ({
  slot_id,
  character_id,
  aria_label,
  on_close,
  on_action,
  show_actions = true,
  footer_options,
  notice,
}: InventoryItemDetailStackProps): ReactNode => {
  const renderNotice = (): ReactNode => {
    if (!notice) {
      return null;
    }

    return <div className="px-4 pb-4">{notice}</div>;
  };

  return (
    <StackedCard
      on_close={on_close}
      aria_label={aria_label}
      content_mode={StackedCardContentMode.FULL_BLEED}
    >
      <InventoryStackBody footer_options={footer_options}>
        {renderNotice()}
        <InventoryItem
          slot_id={slot_id}
          character_id={character_id}
          on_action={on_action}
          show_actions={show_actions}
        />
      </InventoryStackBody>
    </StackedCard>
  );
};

export default InventoryItemDetailStack;
