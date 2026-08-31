import React from 'react';

import QuestRows from './quest-rows';
import Section from '../../viewable-sections/section';
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
    <Section
      title="Quests That Reward for Completing"
      showSeparator={showSeparator}
    >
      {rewardQuests.map((rewardQuest) => (
        <QuestRows
          key={`reward-quest-${rewardQuest.id}`}
          heading="Reward Quest"
          quest={rewardQuest}
          on_open_quest={navigation.on_open_quest}
          on_open_npc={navigation.on_open_npc}
          on_open_map={navigation.on_open_map}
        />
      ))}
    </Section>
  );
};

export default RewardQuestsSection;
