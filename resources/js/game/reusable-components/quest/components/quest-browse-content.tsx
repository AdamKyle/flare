import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useMemo } from 'react';

import QuestCard from './quest-card';
import QuestTree from './quest-tree';
import {
  QUEST_BROWSE_TAB_EMPTY_LABELS,
  QuestBrowseTab,
} from '../enums/quest-browse-tab';
import { QuestTreeState } from '../enums/quest-tree-state';
import QuestBrowseContentProps from '../types/quest-browse-content-props';
import RaidGroupPanelProps from '../types/raid-group-panel-props';
import { buildQuestTreeAccessibilityLabel } from '../utils/build-quest-tree-accessibility-label';
import { groupQuestTreesByRaid } from '../utils/group-quest-trees-by-raid';
import { resolveQuestTreeState } from '../utils/resolve-quest-tree-state';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import { PillTabsAlignment } from 'ui/tabs/enums/pill-tabs-alignment';
import PillTabs from 'ui/tabs/pill-tabs';

const RaidGroupPanel = ({
  quests,
  completed_quest_ids: completedQuestIds,
  navigation,
  tree_mobile_mode: treeMobileMode,
  accessibility_label: accessibilityLabel,
}: RaidGroupPanelProps): ReactNode => (
  <QuestTree
    quests={quests}
    completed_quest_ids={completedQuestIds}
    navigation={navigation}
    mobile_mode={treeMobileMode}
    accessibility_label={accessibilityLabel}
  />
);

interface OneOffDisplayModel {
  quest_id: number;
  name: string;
  state: QuestTreeState;
  npc_name: string | null;
}

/**
 * Shared, permission-neutral Quest browse content: renders the currently
 * active Quest category's already-fetched Quest trees using the
 * presentation appropriate to that category — the real top-to-bottom Quest
 * Tree for the normal parent/child story hierarchy, a compact single-column
 * card list for One Offs, and Raid-grouped Trees for Raid Quests, always
 * presented through nested `PillTabs` (even a single Raid gets its own
 * one-tab `PillTabs`). Never imports Admin or Information code; the caller
 * supplies data, navigation, and the factual selected Game Map name used to
 * build contextual Tree accessibility labels.
 */
const QuestBrowseContent = ({
  active_tab: activeTab,
  quests,
  completed_quest_ids: completedQuestIds,
  loading,
  error,
  navigation,
  tree_mobile_mode: treeMobileMode,
  selected_game_map_name: selectedGameMapName,
}: QuestBrowseContentProps): ReactNode => {
  const completedQuestIdSet = useMemo(
    () => new Set(completedQuestIds),
    [completedQuestIds]
  );

  const raidGroups = useMemo(
    () =>
      activeTab === QuestBrowseTab.RAID ? groupQuestTreesByRaid(quests) : [],
    [activeTab, quests]
  );

  const oneOffDisplayModels = useMemo((): OneOffDisplayModel[] => {
    if (activeTab !== QuestBrowseTab.ONE_OFFS) {
      return [];
    }

    return quests.map((quest) => ({
      quest_id: quest.id,
      name: quest.name,
      state: resolveQuestTreeState(quest, completedQuestIdSet),
      npc_name: quest.npc?.name ?? null,
    }));
  }, [activeTab, quests, completedQuestIdSet]);

  const renderOneOffItem = (item: OneOffDisplayModel): ReactNode => (
    <li key={item.quest_id}>
      <QuestCard
        quest_id={item.quest_id}
        name={item.name}
        state={item.state}
        npc_name={item.npc_name}
        on_open_quest={(id) => navigation?.on_open_quest?.(id)}
      />
    </li>
  );

  const renderOneOffs = (): ReactNode => (
    <ul className="flex flex-col gap-2">
      {oneOffDisplayModels.map(renderOneOffItem)}
    </ul>
  );

  const renderRaid = (): ReactNode => {
    if (raidGroups.length === 0) {
      return (
        <p className="text-glacier-600 dark:text-glacier-400 text-sm">
          {QUEST_BROWSE_TAB_EMPTY_LABELS[QuestBrowseTab.RAID]}
        </p>
      );
    }

    const tabs = raidGroups.map((group) => ({
      label: group.raid_name,
      component: RaidGroupPanel,
      props: {
        quests: group.quests,
        completed_quest_ids: completedQuestIds,
        navigation,
        tree_mobile_mode: treeMobileMode,
        accessibility_label: buildQuestTreeAccessibilityLabel(
          selectedGameMapName,
          group.raid_name
        ),
      },
    }));

    return (
      <PillTabs<RaidGroupPanelProps[]>
        tabs={tabs}
        ariaLabel="Raids"
        alignment={PillTabsAlignment.CENTER}
      />
    );
  };

  const renderActiveTab = (): ReactNode => {
    if (quests.length === 0) {
      return (
        <p className="text-glacier-600 dark:text-glacier-400 text-sm">
          {QUEST_BROWSE_TAB_EMPTY_LABELS[activeTab]}
        </p>
      );
    }

    if (activeTab === QuestBrowseTab.ONE_OFFS) {
      return renderOneOffs();
    }

    if (activeTab === QuestBrowseTab.RAID) {
      return renderRaid();
    }

    return (
      <QuestTree
        quests={quests}
        completed_quest_ids={completedQuestIds}
        navigation={navigation}
        mobile_mode={treeMobileMode}
        accessibility_label={buildQuestTreeAccessibilityLabel(
          selectedGameMapName
        )}
      />
    );
  };

  if (loading) {
    return <InfiniteLoader />;
  }

  if (error) {
    return <ApiErrorAlert apiError={error.message} />;
  }

  return renderActiveTab();
};

export default QuestBrowseContent;
