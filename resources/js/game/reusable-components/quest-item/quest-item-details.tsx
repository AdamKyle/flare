import React from 'react';

import DropSection from './partials/drop-section';
import LocationsRequireSection from './partials/location-requirements-section';
import ModifiersSection from './partials/modifier-section';
import MonsterDropsSection from './partials/monster-drop-section';
import QuestsThatUseSection from './partials/quests-that-use-section';
import RewardLocationsSection from './partials/reward-location-section';
import RewardQuestsSection from './partials/reward-quests-section';
import QuestItemDetailsProps from './types/quest-item-details-props';

type SectionCommonProps = {
  item: QuestItemDetailsProps['item'];
  showSeparator: boolean;
};

type SectionCandidate = {
  enabled: boolean;
  node: React.ReactElement<SectionCommonProps>;
};

const QuestItemDetails = ({ item }: QuestItemDetailsProps) => {
  const hasModifiers =
    item.move_time_out_mod_bonus !== 0 || item.fight_time_out_mod_bonus !== 0;
  const hasMonsterDrops = item.required_monster != null;
  const requiredQuests = item.required_quests || [];
  const hasRequiredQuestSingle = item.required_quest != null;
  const hasRequiredQuestsList = requiredQuests.length > 0;
  const hasQuestsThatUse = hasRequiredQuestSingle || hasRequiredQuestsList;
  const requiredLocations = item.required_locations || [];
  const hasLocationsRequire = requiredLocations.length > 0;
  const rewardLocations = item.reward_locations || [];
  const hasRewardLocations = rewardLocations.length > 0;
  const rewardQuests = item.reward_quests || [];
  const hasRewardQuests = rewardQuests.length > 0;
  const hasDrop = item.drop_location != null;

  const candidates: SectionCandidate[] = [
    {
      enabled: hasModifiers,
      node: <ModifiersSection key="mod" item={item} showSeparator />,
    },
    {
      enabled: hasMonsterDrops,
      node: <MonsterDropsSection key="mdrop" item={item} showSeparator />,
    },
    {
      enabled: hasQuestsThatUse,
      node: <QuestsThatUseSection key="quse" item={item} showSeparator />,
    },
    {
      enabled: hasLocationsRequire,
      node: <LocationsRequireSection key="lreq" item={item} showSeparator />,
    },
    {
      enabled: hasRewardLocations,
      node: <RewardLocationsSection key="lrew" item={item} showSeparator />,
    },
    {
      enabled: hasRewardQuests,
      node: <RewardQuestsSection key="qrew" item={item} showSeparator />,
    },
    {
      enabled: hasDrop,
      node: <DropSection key="drop" item={item} showSeparator />,
    },
  ];

  const sections: React.ReactElement<SectionCommonProps>[] = [];
  for (const candidate of candidates) {
    if (candidate.enabled) {
      sections.push(candidate.node);
    }
  }

  const lastIndex = sections.length - 1;

  return (
    <div className="flex max-w-none flex-col gap-2">
      {sections.map((section, index) =>
        React.cloneElement<SectionCommonProps>(section, {
          showSeparator: index !== lastIndex,
        })
      )}
    </div>
  );
};

export default QuestItemDetails;
