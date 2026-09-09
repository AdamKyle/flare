import React, { ReactNode, useEffect, useRef, useState } from 'react';

import { useCharacterQuestBrowseOptions } from './quests/api/hooks/use-character-quest-browse-options';
import { useCharacterQuestTree } from './quests/api/hooks/use-character-quest-tree';
import CharacterQuestBrowsePanel from './quests/components/character-quest-browse-panel';
import CharacterQuestDetailStack from './quests/components/character-quest-detail-stack';
import { useCharacterQuestWebsocket } from './quests/websockets/hooks/use-character-quest-websocket';
import {
  QUEST_BROWSE_TAB_KIND,
  QuestBrowseTab,
} from '../../reusable-components/quest/enums/quest-browse-tab';
import { buildQuestTreeQueryKey } from '../../reusable-components/quest/utils/build-quest-tree-query-key';

import { useGameData } from 'game-data/hooks/use-game-data';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const CharacterQuests = (): ReactNode => {
  const { gameData } = useGameData();

  const characterId = gameData?.character?.id ?? 0;
  const currentGameMapId = gameData?.character?.game_map_id ?? null;

  const [gameMapId, setGameMapId] = useState<number | null>(null);
  const [activeTab, setActiveTab] = useState<QuestBrowseTab>(
    QuestBrowseTab.BASE
  );
  const [openQuestId, setOpenQuestId] = useState<number | null>(null);

  const hasInitializedMapRef = useRef(false);
  const previousCharacterMapIdRef = useRef<number | null>(null);

  const {
    options,
    loading: optionsLoading,
    error: optionsError,
    refresh: refreshOptions,
  } = useCharacterQuestBrowseOptions({ characterId });

  const activeKind = QUEST_BROWSE_TAB_KIND[activeTab];

  const {
    quests,
    completedQuestIds,
    loading: treeLoading,
    error: treeError,
    queryKey: resultQueryKey,
    refresh: refreshTree,
    replaceCompletedQuestIds,
  } = useCharacterQuestTree({
    characterId,
    mapId: gameMapId,
    kind: activeKind,
  });

  const currentQueryKey = buildQuestTreeQueryKey(gameMapId, activeKind);
  const hasCurrentQueryResult = resultQueryKey === currentQueryKey;

  const visibleQuests = hasCurrentQueryResult ? quests : [];
  const visibleError = hasCurrentQueryResult ? treeError : null;
  const visibleLoading =
    optionsLoading ||
    treeLoading ||
    (currentQueryKey !== null && !hasCurrentQueryResult);

  const showRaidTab =
    options?.active_raid_map_ids?.includes(gameMapId ?? -1) === true;

  useEffect(() => {
    if (!options) {
      return;
    }

    const visibleMapIds = options.game_maps.map((gameMap) => gameMap.id);

    if (!hasInitializedMapRef.current) {
      hasInitializedMapRef.current = true;
      previousCharacterMapIdRef.current = currentGameMapId;

      setGameMapId(
        visibleMapIds.includes(currentGameMapId as number)
          ? currentGameMapId
          : options.default_game_map_id
      );

      return;
    }

    if (gameMapId !== null && visibleMapIds.includes(gameMapId)) {
      return;
    }

    setGameMapId(
      visibleMapIds.includes(currentGameMapId as number)
        ? currentGameMapId
        : options.default_game_map_id
    );
  }, [options, currentGameMapId, gameMapId]);

  useEffect(() => {
    if (!options || currentGameMapId === null) {
      return;
    }

    if (previousCharacterMapIdRef.current === currentGameMapId) {
      return;
    }

    previousCharacterMapIdRef.current = currentGameMapId;

    const characterMapIsVisible = options.game_maps.some(
      (gameMap) => gameMap.id === currentGameMapId
    );

    if (characterMapIsVisible) {
      setGameMapId(currentGameMapId);
    }
  }, [currentGameMapId, options]);

  useEffect(() => {
    if (activeTab === QuestBrowseTab.RAID && !showRaidTab) {
      setActiveTab(QuestBrowseTab.BASE);
    }
  }, [activeTab, showRaidTab]);

  useCharacterQuestWebsocket({
    enabled: characterId > 0,
    onQuestsUpdated: (): void => {
      refreshOptions();
      refreshTree();
    },
  });

  const gameMapItems: DropdownItem[] =
    options?.game_maps.map((gameMap) => ({
      label: gameMap.name,
      value: gameMap.id,
    })) ?? [];

  const selectedGameMapName =
    options?.game_maps.find((gameMap) => gameMap.id === gameMapId)?.name ??
    null;

  const handleCompletedQuestsChange = (ids: number[]): void => {
    replaceCompletedQuestIds(ids);
    refreshTree();
  };

  const handleOpenQuestFromBrowse = (questId: number): void => {
    setOpenQuestId(questId);
  };

  const handleCloseDetail = (): void => {
    setOpenQuestId(null);
  };

  return (
    <div className="relative min-h-0">
      <CharacterQuestBrowsePanel
        game_maps={gameMapItems}
        selected_game_map_id={gameMapId}
        selected_game_map_name={selectedGameMapName}
        active_tab={activeTab}
        show_raid_tab={showRaidTab}
        quests={visibleQuests}
        completed_quest_ids={completedQuestIds}
        loading={visibleLoading}
        error={visibleError ?? optionsError}
        on_select_game_map={setGameMapId}
        on_active_tab_change={setActiveTab}
        on_open_quest={handleOpenQuestFromBrowse}
      />

      {openQuestId !== null && (
        <CharacterQuestDetailStack
          character_id={characterId}
          quest_id={openQuestId}
          completed_quest_ids={completedQuestIds}
          on_close={handleCloseDetail}
          on_completed_quests_change={handleCompletedQuestsChange}
        />
      )}
    </div>
  );
};

export default CharacterQuests;
