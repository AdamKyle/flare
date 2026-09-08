import React, { ReactNode } from 'react';

import CharacterQuestBrowsePanelProps from './types/character-quest-browse-panel-props';
import QuestBrowseControls from '../../../../reusable-components/quest/components/quest-browse-controls';
import {
  QUEST_BROWSE_TABS_ORDER,
  QuestBrowseTab,
} from '../../../../reusable-components/quest/enums/quest-browse-tab';
import { buildQuestBrowseTabs } from '../../../../reusable-components/quest/utils/build-quest-browse-tabs';

import { PillTabsAlignment } from 'ui/tabs/enums/pill-tabs-alignment';
import PillTabs from 'ui/tabs/pill-tabs';
import TreeMobileMode from 'ui/tree/enums/tree-mobile-mode';

const CharacterQuestBrowsePanel = ({
  game_maps: gameMaps,
  selected_game_map_id: selectedGameMapId,
  selected_game_map_name: selectedGameMapName,
  active_tab: activeTab,
  show_raid_tab: showRaidTab,
  quests,
  completed_quest_ids: completedQuestIds,
  loading,
  error,
  on_select_game_map: onSelectGameMap,
  on_active_tab_change: onActiveTabChange,
  on_open_quest: onOpenQuest,
}: CharacterQuestBrowsePanelProps): ReactNode => {
  const visibleTabOrder = QUEST_BROWSE_TABS_ORDER.filter(
    (tab) => showRaidTab || tab !== QuestBrowseTab.RAID
  );

  const activeTabIndex = visibleTabOrder.indexOf(activeTab);

  const handleActiveTabIndexChange = (index: number): void => {
    const selectedTab = visibleTabOrder[index];

    if (!selectedTab) {
      return;
    }

    onActiveTabChange(selectedTab);
  };

  const allTabs = buildQuestBrowseTabs({
    quests,
    completed_quest_ids: completedQuestIds,
    loading,
    error,
    navigation: { on_open_quest: onOpenQuest },
    tree_mobile_mode: TreeMobileMode.ONLY_WHATS_AVAILABLE,
    selected_game_map_name: selectedGameMapName,
  });

  const tabs = QUEST_BROWSE_TABS_ORDER.map((tab, index) => ({
    tab,
    entry: allTabs[index],
  }))
    .filter(({ tab }) => showRaidTab || tab !== QuestBrowseTab.RAID)
    .map(({ entry }) => entry);

  return (
    <div>
      <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
        <QuestBrowseControls
          id="character-quest-plane-filter"
          game_maps={gameMaps}
          selected_game_map_id={selectedGameMapId}
          on_select_game_map={onSelectGameMap}
        />
        <a
          href="/information/quests"
          target="_blank"
          rel="noopener noreferrer"
          className="text-danube-700 dark:text-danube-300 text-sm font-semibold hover:underline"
        >
          Quests help
        </a>
      </div>

      <PillTabs
        tabs={tabs}
        ariaLabel="Quest category"
        alignment={PillTabsAlignment.CENTER}
        activeIndex={activeTabIndex}
        onActiveIndexChange={handleActiveTabIndexChange}
      />
    </div>
  );
};

export default CharacterQuestBrowsePanel;
