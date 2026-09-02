import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useEffect, useRef, useState } from 'react';

import {
  QUEST_BROWSE_TAB_KIND,
  QUEST_BROWSE_TABS_ORDER,
  QuestBrowseTab,
} from '../../../game/reusable-components/quest/enums/quest-browse-tab';
import { buildQuestBrowseTabs } from '../../../game/reusable-components/quest/utils/build-quest-browse-tabs';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { useImportQuests } from '../api/hooks/use-import-quests';
import { useQuestBrowseOptions } from '../api/hooks/use-quest-browse-options';
import { useQuestTree } from '../api/hooks/use-quest-tree';
import QuestBrowseControls from '../components/browse/quest-browse-controls';
import { QuestScreens } from '../screen-manager/quest-screen-constants';
import { useQuestScreenNavigation } from '../screen-manager/quest-screen-kit';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import { PillTabsAlignment } from 'ui/tabs/enums/pill-tabs-alignment';
import PillTabs from 'ui/tabs/pill-tabs';

const QuestListScreen = (): ReactNode => {
  const navigation = useQuestScreenNavigation();
  const [gameMapId, setGameMapId] = useState<number | null>(null);
  const [activeTab, setActiveTab] = useState<QuestBrowseTab>(
    QuestBrowseTab.BASE
  );
  const [announcement, setAnnouncement] = useState('');

  const hasInitializedMapRef = useRef(false);

  const {
    options,
    loading: optionsLoading,
    error: optionsError,
  } = useQuestBrowseOptions();
  const {
    quests,
    loading: treeLoading,
    error,
    refresh,
  } = useQuestTree(gameMapId, QUEST_BROWSE_TAB_KIND[activeTab]);
  const { import_quests: importQuests, importing } = useImportQuests();

  const fileInputRef = useRef<HTMLInputElement>(null);

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

  const handleCreate = (): void => {
    navigation.navigateTo(QuestScreens.FORM, {
      quest_id: null,
      parent_quest_id: null,
    });
  };

  const handleOpenQuest = (id: number): void => {
    navigation.navigateTo(QuestScreens.SHOW, { quest_id: id });
  };

  const handleImportClick = (): void => {
    fileInputRef.current?.click();
  };

  const handleFileSelected = async (
    event: React.ChangeEvent<HTMLInputElement>
  ): Promise<void> => {
    const file = event.target.files?.[0];
    event.target.value = '';

    if (!file) {
      return;
    }

    const imported = await importQuests(file);

    if (imported) {
      setAnnouncement('Quests imported successfully.');
      refresh();
    }
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
      quests,
      completed_quest_ids: [],
      loading: treeLoading,
      error,
      navigation: { on_open_quest: handleOpenQuest },
    });

    return (
      <PillTabs
        tabs={tabs}
        ariaLabel="Quest category"
        alignment={PillTabsAlignment.START}
        onActiveIndexChange={(index) =>
          setActiveTab(QUEST_BROWSE_TABS_ORDER[index])
        }
      />
    );
  };

  return (
    <AdminPage
      title="Quests"
      width={AdminPageWidth.Workspace}
      header_actions={
        <>
          <a
            href="/admin"
            className="focus-visible:ring-glacier-400 border-glacier-300 text-glacier-700 hover:bg-glacier-50 dark:border-glacier-700 dark:bg-glacier-950 dark:text-glacier-200 dark:hover:bg-glacier-900 rounded-md border bg-white px-3 py-1.5 text-sm font-medium focus:outline-none focus-visible:ring-2"
          >
            Back
          </a>
          <Button
            label="Create Quest"
            variant={ButtonVariant.PRIMARY}
            on_click={handleCreate}
          />
        </>
      }
    >
      <div className="w-full min-w-0 px-4 sm:px-6 lg:px-8">
        <QuestBrowseControls
          game_maps={gameMapItems}
          selected_game_map_id={gameMapId}
          on_select_game_map={setGameMapId}
          importing={importing}
          on_import_click={handleImportClick}
        />
        <input
          ref={fileInputRef}
          type="file"
          accept=".xlsx,.xls"
          className="sr-only"
          aria-label="Import Quests workbook"
          onChange={(event) => void handleFileSelected(event)}
        />

        {renderContent()}

        <p className="sr-only" role="status" aria-live="polite">
          {announcement}
        </p>
      </div>
    </AdminPage>
  );
};

export default QuestListScreen;
