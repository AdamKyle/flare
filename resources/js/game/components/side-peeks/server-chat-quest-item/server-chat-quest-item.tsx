import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React from 'react';

import { useGuideTarget } from '../../guide-quests/walkthrough/hooks/use-guide-target';
import { GuideTargets } from '../../guide-quests/walkthrough/registry/guide-targets';
import { useInventoryQuestItemSlot } from '../character-inventory/inventory-item/api/hooks/use-inventory-quest-item-slot';
import QuestItemDetailBody from '../character-inventory/inventory-item/quest-item-detail-body';
import ServerChatItemProps from '../server-chat-item/types/server-chat-item-props';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const ServerChatQuestItem = ({
  slot_id: slotId,
  character_id: characterId,
}: ServerChatItemProps) => {
  const questItemGuideTarget = useGuideTarget(GuideTargets.QUEST_ITEM_DETAILS);
  const {
    quest_item: questItem,
    loading,
    error,
  } = useInventoryQuestItemSlot(characterId, slotId);

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
        <ApiErrorAlert apiError={error ?? 'Unable to load this quest item.'} />
      </div>
    );
  }

  return (
    <div {...questItemGuideTarget}>
      <QuestItemDetailBody quest_item={questItem} />
    </div>
  );
};

export default ServerChatQuestItem;
