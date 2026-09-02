import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useEffect, useRef, useState } from 'react';

import { useAdminGameMapFilterOptions } from '../../shared/api/hooks/use-admin-game-map-filter-options';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import MonsterListDefinition from '../api/definitions/monster-list-definition';
import { useImportMonsters } from '../api/hooks/use-import-monsters';
import { useMonsters } from '../api/hooks/use-monsters';
import {
  MONSTER_LIST_CATEGORIES_WITH_LOCATION_TYPE,
  MONSTER_LIST_CATEGORY_LABELS,
  MONSTER_LIST_CATEGORY_VALUES,
  isMonsterListCategory,
} from '../enums/monster-list-category';
import { MonsterScreens } from '../screen-manager/monster-screen-constants';
import { useMonsterScreenNavigation } from '../screen-manager/monster-screen-kit';
import { buildMonsterListColumns } from '../utils/build-monster-list-columns';
import { buildMonsterLocationTypeItems } from '../utils/build-monster-location-type-items';
import { parseNumberOption } from '../utils/parse-monster-dropdown-value';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import DataTable from 'ui/data-table/data-table';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const MonsterListScreen = (): ReactNode => {
  const navigation = useMonsterScreenNavigation();
  const [announcement, setAnnouncement] = useState('');
  const fileInputRef = useRef<HTMLInputElement>(null);
  const hasInitializedMapRef = useRef(false);

  const {
    options: mapOptions,
    loading: mapOptionsLoading,
    error: mapOptionsError,
  } = useAdminGameMapFilterOptions();
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
    category,
    set_category: setCategory,
    location_type: locationType,
    set_location_type: setLocationType,
    sort_key: sortKey,
    sort_direction: sortDirection,
    set_sort: setSort,
    refresh_first_page: refreshFirstPage,
  } = useMonsters();

  useEffect(() => {
    if (hasInitializedMapRef.current || !mapOptions) {
      return;
    }

    hasInitializedMapRef.current = true;
    setGameMapId(mapOptions.default_game_map_id);
  }, [mapOptions, setGameMapId]);

  const gameMapItems: DropdownItem[] =
    mapOptions?.game_maps.map((gameMap) => ({
      label: gameMap.name,
      value: gameMap.id,
    })) ?? [];
  const columns = buildMonsterListColumns();

  const categoryItems: DropdownItem[] = MONSTER_LIST_CATEGORY_VALUES.map(
    (categoryValue) => ({
      value: categoryValue,
      label: MONSTER_LIST_CATEGORY_LABELS[categoryValue],
    })
  );
  const showLocationTypeFilter =
    MONSTER_LIST_CATEGORIES_WITH_LOCATION_TYPE.includes(category);
  const locationTypeItems: DropdownItem[] =
    buildMonsterLocationTypeItems(category);

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

  const renderFilters = (): ReactNode => {
    if (mapOptionsLoading && !hasInitializedMapRef.current) {
      return <InfiniteLoader />;
    }

    if (mapOptionsError) {
      return <ApiErrorAlert apiError={mapOptionsError.message} />;
    }

    return (
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

        <div className="w-full max-w-xs">
          <Dropdown
            id="monster-category-filter"
            aria_label="Filter by Monster Category"
            items={categoryItems}
            pre_selected_item={categoryItems.find(
              (item) => item.value === category
            )}
            on_select={(item) => {
              if (isMonsterListCategory(item.value)) {
                setCategory(item.value);
              }
            }}
            selection_placeholder="All Monsters"
          />
        </div>

        {showLocationTypeFilter && (
          <div className="w-full max-w-xs">
            <Dropdown
              id="monster-location-type-filter"
              aria_label="Filter by Location Type"
              searchable
              items={locationTypeItems}
              pre_selected_item={locationTypeItems.find(
                (item) => item.value === locationType
              )}
              on_select={(item) =>
                setLocationType(parseNumberOption(item.value))
              }
              on_clear={() => setLocationType(null)}
              selection_placeholder="All Location Types"
            />
          </div>
        )}

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
    );
  };

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
      {renderFilters()}

      <DataTable<MonsterListDefinition>
        id_prefix="monsters"
        caption="Monsters"
        rows={data}
        row_id={(row) => row.id}
        columns={columns}
        loading={loading}
        error={error?.message ?? null}
        empty_message="No Monsters match the current search and filters."
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
