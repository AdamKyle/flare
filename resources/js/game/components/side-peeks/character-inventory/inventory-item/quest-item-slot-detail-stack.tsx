import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode } from 'react';

import { useInventoryQuestItemSlot } from './api/hooks/use-inventory-quest-item-slot';
import QuestItemDetailBody from './quest-item-detail-body';
import QuestItemSlotDetailStackProps from './types/quest-item-slot-detail-stack-props';

import { StackedCardContentMode } from 'ui/cards/enums/stacked-card-content-mode';
import StackedCard from 'ui/cards/stacked-card';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const QuestItemSlotDetailStack = ({
  character_id: characterId,
  slot_id: slotId,
  aria_label: ariaLabel,
  on_close: onClose,
}: QuestItemSlotDetailStackProps): ReactNode => {
  const {
    quest_item: questItem,
    loading,
    error,
  } = useInventoryQuestItemSlot(characterId, slotId);

  const renderContent = (): ReactNode => {
    if (loading) {
      return (
        <div className="px-4 py-4">
          <InfiniteLoader />
        </div>
      );
    }

    if (error !== null || questItem === null) {
      return (
        <div className="px-4 py-4">
          <ApiErrorAlert
            apiError={error ?? 'Unable to load this quest item.'}
          />
        </div>
      );
    }

    return <QuestItemDetailBody quest_item={questItem} />;
  };

  return (
    <StackedCard
      on_close={onClose}
      aria_label={ariaLabel}
      content_mode={StackedCardContentMode.FULL_BLEED}
    >
      <div className="relative flex h-full min-h-0 flex-col overflow-hidden">
        {renderContent()}
      </div>
    </StackedCard>
  );
};

export default QuestItemSlotDetailStack;
