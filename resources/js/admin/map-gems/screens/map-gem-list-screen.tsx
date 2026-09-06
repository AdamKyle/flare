import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import { useAdminGameMapFilterOptions } from '../../shared/api/hooks/use-admin-game-map-filter-options';
import AdminAnchorButton from '../../shared/components/admin-anchor-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import MapGemListDefinition from '../api/definitions/map-gem-list-definition';
import { useMapGems } from '../api/hooks/use-map-gems';
import { useRollAllMapGems } from '../api/hooks/use-roll-all-map-gems';
import { MapGemImportCopy } from '../enums/map-gem-import-copy';
import { useOpenMapGemBulkRollResultSidePeek } from '../hooks/use-open-map-gem-bulk-roll-result-side-peek';
import { useOpenMapGemImportSidePeek } from '../hooks/use-open-map-gem-import-side-peek';
import { MapGemScreens } from '../screen-manager/map-gem-screen-constants';
import { useMapGemScreenNavigation } from '../screen-manager/map-gem-screen-kit';
import { parseNumberOption } from '../utils/parse-map-gem-dropdown-value';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import DataTable from 'ui/data-table/data-table';
import DataTableColumnDefinition from 'ui/data-table/types/data-table-column-definition';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const MapGemListScreen = (): ReactNode => {
  const navigation = useMapGemScreenNavigation();
  const [announcement, setAnnouncement] = useState('');
  const { options: mapOptions } = useAdminGameMapFilterOptions();

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
  } = useMapGems();

  const gameMapItems: DropdownItem[] =
    mapOptions?.game_maps.map((gameMap) => ({
      label: gameMap.name,
      value: gameMap.id,
    })) ?? [];

  const columns: DataTableColumnDefinition<MapGemListDefinition>[] = [
    { key: 'name', header: 'Name', value: (row) => row.name, sortable: true },
    {
      key: 'game_map',
      header: 'Game Map',
      value: (row) => row.game_map.name,
    },
    {
      key: 'roll_count',
      header: 'Roll Count',
      value: (row) => String(row.roll_count),
      sortable: true,
    },
    {
      key: 'rolled_gem',
      header: 'Active Roll',
      value: (row) =>
        row.rolled_gem ? `Roll #${row.rolled_gem.roll_number}` : 'None',
    },
  ];

  const handleRowActivate = (row: MapGemListDefinition): void => {
    navigation.navigateTo(MapGemScreens.SHOW, { map_gem_id: row.id });
  };

  const handleCreate = (): void => {
    navigation.navigateTo(MapGemScreens.FORM, { map_gem_id: null });
  };

  const handleImported = (): void => {
    setAnnouncement(MapGemImportCopy.SuccessAnnouncement);
    refreshFirstPage();
  };

  const handleImport = useOpenMapGemImportSidePeek(handleImported);

  const {
    rolling,
    error: rollAllError,
    roll_all: rollAll,
  } = useRollAllMapGems();
  const openBulkRollResult = useOpenMapGemBulkRollResultSidePeek();

  const handleRollAll = async (): Promise<void> => {
    const result = await rollAll();

    if (result) {
      setAnnouncement(`Rolled ${result.rolled_count} Map Gems.`);
      refreshFirstPage();
      openBulkRollResult(result);
    }
  };

  const totalPages = response?.meta.pagination.total_pages ?? 0;
  const totalRecords = response?.meta.pagination.total ?? 0;

  return (
    <AdminPage
      title="Map Gems"
      width={AdminPageWidth.Standard}
      header_actions={
        <>
          <AdminAnchorButton
            href="/admin"
            label="Back"
            variant={ButtonVariant.DANGER}
          />
          <Button
            label="Create Map Gem"
            variant={ButtonVariant.PRIMARY}
            on_click={handleCreate}
          />
        </>
      }
    >
      <div className="mb-4 flex flex-wrap items-center gap-3">
        <Button
          label="Import"
          variant={ButtonVariant.PRIMARY}
          on_click={handleImport}
        />
        <AdminAnchorButton
          href="/admin/map-gems/export"
          label="Export"
          variant={ButtonVariant.PRIMARY}
        />
        <Button
          label={rolling ? 'Rolling…' : 'Roll All'}
          variant={ButtonVariant.SUCCESS}
          on_click={() => void handleRollAll()}
          disabled={rolling}
        />
        <div className="w-full max-w-xs">
          <Dropdown
            id="map-gem-map-filter"
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
      </div>

      {rollAllError && (
        <div className="mb-4">
          <ApiErrorAlert apiError={rollAllError.message} />
        </div>
      )}

      <DataTable<MapGemListDefinition>
        id_prefix="map-gems"
        caption="Map Gems"
        rows={data}
        row_id={(row) => row.id}
        columns={columns}
        loading={loading}
        error={error?.message ?? null}
        empty_message="No Map Gem profiles match the current search and filters."
        search_label="Search Map Gems"
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

export default MapGemListScreen;
