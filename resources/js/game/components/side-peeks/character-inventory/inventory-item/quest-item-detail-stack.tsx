import React, { ReactNode } from 'react';

import QuestItemDetailBody from './quest-item-detail-body';
import QuestItemDetailStackProps from './types/quest-item-detail-stack-props';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';

const QuestItemDetailStack = ({
  quest_item: questItem,
  on_close: onClose,
}: QuestItemDetailStackProps): ReactNode => {
  return (
    <StackedCard
      on_close={onClose}
      aria_label="Quest Item Details"
      content_mode={StackedCardContentMode.FULL_BLEED}
    >
      <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
        <QuestItemDetailBody quest_item={questItem} />
      </div>
    </StackedCard>
  );
};

export default QuestItemDetailStack;
