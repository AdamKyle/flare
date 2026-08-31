import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useRef, useState } from 'react';

import QuestTree from '../../../game/reusable-components/quest/components/quest-tree';
import {
  isQuestKind,
  QUEST_KIND_LABELS,
  QuestKind,
} from '../../../game/reusable-components/quest/enums/quest-kind';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import { useImportQuests } from '../api/hooks/use-import-quests';
import { useQuestFormOptions } from '../api/hooks/use-quest-form-options';
import { useQuestTree } from '../api/hooks/use-quest-tree';
import { QuestScreens } from '../screen-manager/quest-screen-constants';
import { useQuestScreenNavigation } from '../screen-manager/quest-screen-kit';
import { parseNumberOption } from '../utils/parse-quest-dropdown-value';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const KIND_ITEMS: DropdownItem[] = Object.values(QuestKind).map((kind) => ({
  label: QUEST_KIND_LABELS[kind],
  value: kind,
}));

const QuestListScreen = (): ReactNode => {
  const navigation = useQuestScreenNavigation();
  const [gameMapId, setGameMapId] = useState<number | null>(null);
  const [kind, setKind] = useState<QuestKind | null>(null);
  const [announcement, setAnnouncement] = useState('');

  const { form_options: formOptions } = useQuestFormOptions();
  const { quests, loading, error, refresh } = useQuestTree(gameMapId, kind);
  const { import_quests: importQuests, importing } = useImportQuests();

  const fileInputRef = useRef<HTMLInputElement>(null);

  const gameMapItems: DropdownItem[] = formOptions?.game_maps ?? [];

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
    if (loading) {
      return <InfiniteLoader />;
    }

    if (error) {
      return <ApiErrorAlert apiError={error.message} />;
    }

    if (quests.length === 0) {
      return (
        <p className="text-glacier-600 dark:text-glacier-400 text-sm">
          No Quests match the current filters.
        </p>
      );
    }

    return (
      <QuestTree
        quests={quests}
        completed_quest_ids={[]}
        navigation={{ on_open_quest: handleOpenQuest }}
      />
    );
  };

  return (
    <AdminPage
      title="Quests"
      width={AdminPageWidth.Standard}
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
      <div className="mb-4 flex flex-wrap items-center gap-3">
        <div className="w-full max-w-xs">
          <Dropdown
            id="quest-map-filter"
            aria_label="Filter by Game Map"
            searchable
            items={gameMapItems}
            pre_selected_item={gameMapItems.find(
              (item) => item.value === gameMapId
            )}
            on_select={(item) => setGameMapId(parseNumberOption(item.value))}
            on_clear={() => setGameMapId(null)}
            selection_placeholder="All Maps"
          />
        </div>

        <div className="w-full max-w-xs">
          <Dropdown
            id="quest-kind-filter"
            aria_label="Filter by Quest kind"
            items={KIND_ITEMS}
            pre_selected_item={KIND_ITEMS.find((item) => item.value === kind)}
            on_select={(item) => {
              if (!isQuestKind(item.value)) {
                return;
              }

              setKind(item.value);
            }}
            on_clear={() => setKind(null)}
            selection_placeholder="All Kinds"
          />
        </div>

        <Button
          label={importing ? 'Importing…' : 'Import'}
          variant={ButtonVariant.PRIMARY}
          on_click={handleImportClick}
          disabled={importing}
        />
        <input
          ref={fileInputRef}
          type="file"
          accept=".xlsx,.xls"
          className="sr-only"
          aria-label="Import Quests workbook"
          onChange={(event) => void handleFileSelected(event)}
        />
        <a
          href="/admin/quests/export"
          className="focus-visible:ring-glacier-400 border-glacier-300 text-glacier-700 hover:bg-glacier-50 dark:border-glacier-700 dark:bg-glacier-950 dark:text-glacier-200 dark:hover:bg-glacier-900 rounded-md border bg-white px-3 py-1.5 text-sm font-medium focus:outline-none focus-visible:ring-2"
        >
          Export
        </a>
      </div>

      {renderContent()}

      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
    </AdminPage>
  );
};

export default QuestListScreen;
