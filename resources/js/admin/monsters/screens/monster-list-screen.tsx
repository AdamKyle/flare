import React, { ReactNode, useRef, useState } from 'react';

import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import MonsterListDefinition from '../api/definitions/monster-list-definition';
import { useImportMonsters } from '../api/hooks/use-import-monsters';
import { useMonsterFormOptions } from '../api/hooks/use-monster-form-options';
import { useMonsters } from '../api/hooks/use-monsters';
import { MonsterScreens } from '../screen-manager/monster-screen-constants';
import { useMonsterScreenNavigation } from '../screen-manager/monster-screen-kit';
import { buildMonsterListColumns } from '../utils/build-monster-list-columns';
import { parseNumberOption } from '../utils/parse-monster-dropdown-value';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import DataTable from 'ui/data-table/data-table';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const MonsterListScreen = (): ReactNode => {
  const navigation = useMonsterScreenNavigation();
  const [announcement, setAnnouncement] = useState('');
  const fileInputRef = useRef<HTMLInputElement>(null);

  const { form_options: formOptions } = useMonsterFormOptions();
  const { import_monsters: importMonsters, importing } = useImportMonsters();

  const {
    data,
    loading,
    error,
    response,
    search_text: searchText,
    set_search_text: setSearchText,
    page,
    set_page: setPage,
    game_map_id: gameMapId,
    set_game_map_id: setGameMapId,
    sort_key: sortKey,
    sort_direction: sortDirection,
    set_sort: setSort,
    refresh_first_page: refreshFirstPage,
  } = useMonsters();

  const gameMapItems: DropdownItem[] = formOptions?.game_maps ?? [];
  const columns = buildMonsterListColumns();

  const handleRowActivate = (monster: MonsterListDefinition): void => {
    navigation.navigateTo(MonsterScreens.SHOW, { monster_id: monster.id });
  };

  const handleCreate = (): void => {
    navigation.navigateTo(MonsterScreens.FORM, { monster_id: null });
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

    const imported = await importMonsters(file);

    if (imported) {
      setAnnouncement('Monsters imported successfully.');
      refreshFirstPage();
    }
  };

  const totalPages = response?.meta.pagination.total_pages ?? 0;
  const totalRecords = response?.meta.pagination.total ?? 0;

  return (
    <AdminPage
      title="Monsters"
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
            label="Create Monster"
            variant={ButtonVariant.PRIMARY}
            on_click={handleCreate}
          />
        </>
      }
    >
      <div className="mb-4 flex flex-wrap items-center gap-3">
        <div className="w-full max-w-xs">
          <Dropdown
            id="monster-map-filter"
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
          aria-label="Import Monsters workbook"
          onChange={(event) => void handleFileSelected(event)}
        />
        <a
          href="/admin/monsters/export"
          className="focus-visible:ring-glacier-400 border-glacier-300 text-glacier-700 hover:bg-glacier-50 dark:border-glacier-700 dark:bg-glacier-950 dark:text-glacier-200 dark:hover:bg-glacier-900 rounded-md border bg-white px-3 py-1.5 text-sm font-medium focus:outline-none focus-visible:ring-2"
        >
          Export
        </a>
      </div>

      <DataTable<MonsterListDefinition>
        id_prefix="monsters"
        caption="Monsters"
        rows={data}
        row_id={(row) => row.id}
        columns={columns}
        loading={loading}
        error={error?.message ?? null}
        empty_message="No Monsters match the current search."
        search_label="Search Monsters"
        search_value={searchText}
        on_search_change={setSearchText}
        current_page={page}
        total_pages={totalPages}
        total_records={totalRecords}
        on_page_change={setPage}
        sort_key={sortKey}
        sort_direction={sortDirection}
        on_sort_change={setSort}
        on_row_activate={handleRowActivate}
      />

      <p className="sr-only" role="status" aria-live="polite">
        {announcement}
      </p>
    </AdminPage>
  );
};

export default MonsterListScreen;
