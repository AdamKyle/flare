import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useEffect, useRef, useState } from 'react';

import QuestBrowseControls from '../../../game/reusable-components/quest/components/quest-browse-controls';
import {
  QUEST_BROWSE_TAB_KIND,
  QUEST_BROWSE_TABS_ORDER,
  QuestBrowseTab,
} from '../../../game/reusable-components/quest/enums/quest-browse-tab';
import { buildQuestBrowseTabs } from '../../../game/reusable-components/quest/utils/build-quest-browse-tabs';
import { buildQuestTreeQueryKey } from '../../../game/reusable-components/quest/utils/build-quest-tree-query-key';
import { usePublicQuestBrowseOptions } from '../api/hooks/use-public-quest-browse-options';
import { usePublicQuestTree } from '../api/hooks/use-public-quest-tree';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import { PillTabsAlignment } from 'ui/tabs/enums/pill-tabs-alignment';
import PillTabs from 'ui/tabs/pill-tabs';
import TreeMobileMode from 'ui/tree/enums/tree-mobile-mode';

const navigateToQuest = (id: number): void => {
  window.location.href = `/information/quests/${id}`;
};

const QuestInfoTreePage = (): ReactNode => {
  const [gameMapId, setGameMapId] = useState<number | null>(null);
  const [activeTab, setActiveTab] = useState<QuestBrowseTab>(
    QuestBrowseTab.BASE
  );

  const hasInitializedMapRef = useRef(false);

  const {
    options,
    loading: optionsLoading,
    error: optionsError,
  } = usePublicQuestBrowseOptions();

  const activeKind = QUEST_BROWSE_TAB_KIND[activeTab];
  const activeTabIndex = QUEST_BROWSE_TABS_ORDER.indexOf(activeTab);

  const {
    quests,
    loading: treeLoading,
    error,
    query_key: resultQueryKey,
  } = usePublicQuestTree(gameMapId, activeKind);

  const currentQueryKey = buildQuestTreeQueryKey(gameMapId, activeKind);
  const hasCurrentQueryResult = resultQueryKey === currentQueryKey;

  const visibleQuests = hasCurrentQueryResult ? quests : [];
  const visibleError = hasCurrentQueryResult ? error : null;
  const visibleLoading =
    treeLoading || (currentQueryKey !== null && !hasCurrentQueryResult);

  useEffect(() => {
    if (hasInitializedMapRef.current || !options) {
      return;
    }

    hasInitializedMapRef.current = true;
    setGameMapId(options.default_game_map_id);
  }, [options]);

  const gameMapItems: DropdownItem[] =
    options?.game_maps.map((gameMap) => ({
      label: gameMap.name,
      value: gameMap.id,
    })) ?? [];

  const selectedGameMapName =
    options?.game_maps.find((gameMap) => gameMap.id === gameMapId)?.name ??
    null;

  const handleActiveTabChange = (index: number): void => {
    const selectedTab = QUEST_BROWSE_TABS_ORDER[index];

    if (!selectedTab) {
      return;
    }

    setActiveTab(selectedTab);
  };

  const renderContent = (): ReactNode => {
    if (optionsLoading) {
      return <InfiniteLoader />;
    }

    if (optionsError) {
      return <ApiErrorAlert apiError={optionsError.message} />;
    }

    if (gameMapId === null) {
      return (
        <p className="text-glacier-600 dark:text-glacier-400 text-sm">
          No default Game Map is configured. Select a Game Map to browse Quests.
        </p>
      );
    }

    const tabs = buildQuestBrowseTabs({
      quests: visibleQuests,
      completed_quest_ids: [],
      loading: visibleLoading,
      error: visibleError,
      navigation: { on_open_quest: navigateToQuest },
      tree_mobile_mode: TreeMobileMode.TREE,
      selected_game_map_name: selectedGameMapName,
    });

    return (
      <PillTabs
        tabs={tabs}
        ariaLabel="Quest category"
        alignment={PillTabsAlignment.CENTER}
        activeIndex={activeTabIndex}
        onActiveIndexChange={handleActiveTabChange}
      />
    );
  };

  return (
    <div>
      <QuestBrowseControls
        id="quest-info-plane-filter"
        game_maps={gameMapItems}
        selected_game_map_id={gameMapId}
        on_select_game_map={setGameMapId}
      />

      {renderContent()}
    </div>
  );
};

export default QuestInfoTreePage;
