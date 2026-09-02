import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useMemo } from 'react';

import QuestCard from './quest-card';
import QuestTree from './quest-tree';
import QuestTreeNodeDefinition from '../api/definitions/quest-tree-node-definition';
import {
  QUEST_BROWSE_TAB_EMPTY_LABELS,
  QuestBrowseTab,
} from '../enums/quest-browse-tab';
import QuestBrowseContentProps from '../types/quest-browse-content-props';
import { QuestTreeNavigationDefinition } from '../types/quest-node-props';
import { groupQuestTreesByRaid } from '../utils/group-quest-trees-by-raid';
import { resolveQuestTreeState } from '../utils/resolve-quest-tree-state';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import { PillTabsAlignment } from 'ui/tabs/enums/pill-tabs-alignment';
import PillTabs from 'ui/tabs/pill-tabs';

interface RaidGroupPanelProps {
  quests: QuestTreeNodeDefinition[];
  completed_quest_ids: number[];
  navigation?: QuestTreeNavigationDefinition;
}

const RaidGroupPanel = ({
  quests,
  completed_quest_ids: completedQuestIds,
  navigation,
}: RaidGroupPanelProps): ReactNode => (
  <QuestTree
    quests={quests}
    completed_quest_ids={completedQuestIds}
    navigation={navigation}
  />
);

/**
 * Shared, permission-neutral Quest browse content: renders the currently
 * active Quest category's already-fetched Quest trees using the
 * presentation appropriate to that category — a branching tree for Base, a
 * single-column card list for One Offs, and Raid-grouped trees (nested
 * `PillTabs` when more than one Raid is present) for Raid. Never imports
 * Admin or Information code; the caller supplies data and navigation.
 */
const QuestBrowseContent = ({
  active_tab: activeTab,
  quests,
  completed_quest_ids: completedQuestIds,
  loading,
  error,
  navigation,
}: QuestBrowseContentProps): ReactNode => {
  const raidGroups = useMemo(
    () =>
      activeTab === QuestBrowseTab.RAID ? groupQuestTreesByRaid(quests) : [],
    [activeTab, quests]
  );

  const renderOneOffs = (): ReactNode => (
    <ul className="space-y-3">
      {quests.map((quest) => (
        <li key={quest.id}>
          <QuestCard
            quest_id={quest.id}
            name={quest.name}
            state={resolveQuestTreeState(quest, new Set(completedQuestIds))}
            npc_name={quest.npc?.name ?? null}
            on_open_quest={(id) => navigation?.on_open_quest?.(id)}
          />
        </li>
      ))}
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

    if (raidGroups.length === 1) {
      const group = raidGroups[0];

      return (
        <div>
          <h2 className="text-glacier-900 dark:text-glacier-100 mb-3 text-sm font-semibold">
            {group.raid_name}
          </h2>
          <RaidGroupPanel
            quests={group.quests}
            completed_quest_ids={completedQuestIds}
            navigation={navigation}
          />
        </div>
      );
    }

    const tabs = raidGroups.map((group) => ({
      label: group.raid_name,
      component: RaidGroupPanel,
      props: {
        quests: group.quests,
        completed_quest_ids: completedQuestIds,
        navigation,
      },
    }));

    return (
      <PillTabs<RaidGroupPanelProps[]>
        tabs={tabs}
        ariaLabel="Raids"
        alignment={PillTabsAlignment.START}
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
