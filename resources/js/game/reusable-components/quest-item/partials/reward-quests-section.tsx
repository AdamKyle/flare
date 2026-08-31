import React from 'react';

import QuestRows from './quest-rows';
import RelationshipGroup from './relationship-group';
import RewardQuestsSectionProps from '../types/partials/reward-quest-section-props';

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
        <QuestRows
          key={`reward-quest-${rewardQuest.id}`}
          heading="Rewarded by quest"
          quest={rewardQuest}
          on_open_quest={navigation.on_open_quest}
          on_open_npc={navigation.on_open_npc}
          on_open_map={navigation.on_open_map}
        />
      ))}
    </RelationshipGroup>
  );
};

export default RewardQuestsSection;
