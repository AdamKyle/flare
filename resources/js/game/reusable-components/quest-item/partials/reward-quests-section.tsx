import React from 'react';

import QuestRows from './quest-rows';
import Section from '../../viewable-sections/section';
import RewardQuestsSectionProps from '../types/partials/reward-quest-section-props';

const RewardQuestsSection = ({
  item,
  showSeparator,
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
          name={rewardQuest.name}
          npc={rewardQuest.npc}
          map={rewardQuest.map}
        />
      ))}
    </Section>
  );
};

export default RewardQuestsSection;
