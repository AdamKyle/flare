import React from 'react';

import RelationshipGroup from './relationship-group';
import QuestCard from '../../quest/components/quest-card';
import RewardQuestsSectionProps from '../types/partials/reward-quest-section-props';
import { QuestIdentityDefinition } from '../types/quest-item-factual-definition';

/**
 * Build the canonical "Rewarded by quest" context label, including the
 * factual Map name when the Quest identity carries one.
 */
const buildRewardedByContextLabel = (quest: QuestIdentityDefinition): string =>
  quest.game_map
    ? `Rewarded by quest on ${quest.game_map.name}`
    : 'Rewarded by quest';

const RewardQuestsSection = ({
  item,
  showSeparator,
  navigation,
}: RewardQuestsSectionProps) => {
  const rewardQuests = item.reward_quests || [];

  if (rewardQuests.length === 0) {
    return null;
  }

  return (
    <RelationshipGroup
      title="Quests That Reward for Completing"
      show_separator={showSeparator}
    >
      {rewardQuests.map((rewardQuest) => (
        <QuestCard
          key={`reward-quest-${rewardQuest.id}`}
          quest_id={rewardQuest.id}
          name={rewardQuest.name}
          npc_name={rewardQuest.npc?.name ?? null}
          context_label={buildRewardedByContextLabel(rewardQuest)}
          on_open_quest={navigation.on_open_quest}
        />
      ))}
    </RelationshipGroup>
  );
};

export default RewardQuestsSection;
