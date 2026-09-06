import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useState } from 'react';

import { useAdminGameMapFilterOptions } from '../../shared/api/hooks/use-admin-game-map-filter-options';
import AdminAnchorButton from '../../shared/components/admin-anchor-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import LocationGemListDefinition from '../api/definitions/location-gem-list-definition';
import { useLocationGemFormOptions } from '../api/hooks/use-location-gem-form-options';
import { useLocationGems } from '../api/hooks/use-location-gems';
import { useRollAllLocationGems } from '../api/hooks/use-roll-all-location-gems';
import { LocationGemImportCopy } from '../enums/location-gem-import-copy';
import { useOpenLocationGemBulkRollResultSidePeek } from '../hooks/use-open-location-gem-bulk-roll-result-side-peek';
import { useOpenLocationGemImportSidePeek } from '../hooks/use-open-location-gem-import-side-peek';
import { LocationGemScreens } from '../screen-manager/location-gem-screen-constants';
import { useLocationGemScreenNavigation } from '../screen-manager/location-gem-screen-kit';
import { buildLocationGemOptionLabel } from '../utils/build-location-gem-option-label';
import { parseNumberOption } from '../utils/parse-location-gem-dropdown-value';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import DataTable from 'ui/data-table/data-table';
import DataTableColumnDefinition from 'ui/data-table/types/data-table-column-definition';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const LocationGemListScreen = (): ReactNode => {
  const navigation = useLocationGemScreenNavigation();
  const [announcement, setAnnouncement] = useState('');
  const { options: mapOptions } = useAdminGameMapFilterOptions();
  const { form_options: formOptions } = useLocationGemFormOptions();

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
    location_id: locationId,
    set_location_id: setLocationId,
    sort_key: sortKey,
    sort_direction: sortDirection,
    set_sort: setSort,
    refresh_first_page: refreshFirstPage,
  } = useLocationGems();

  const gameMapItems: DropdownItem[] =
    mapOptions?.game_maps.map((gameMap) => ({
      label: gameMap.name,
      value: gameMap.id,
    })) ?? [];
  const locationItems: DropdownItem[] = (formOptions?.locations ?? []).map(
    (location) => ({
      label: buildLocationGemOptionLabel(location),
      value: location.id,
    })
  );

  const columns: DataTableColumnDefinition<LocationGemListDefinition>[] = [
    { key: 'name', header: 'Name', value: (row) => row.name, sortable: true },
    { key: 'game_map', header: 'Map', value: (row) => row.game_map.name },
    { key: 'location', header: 'Location', value: (row) => row.location.name },
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

  const handleRowActivate = (row: LocationGemListDefinition): void => {
    navigation.navigateTo(LocationGemScreens.SHOW, { location_gem_id: row.id });
  };

  const handleCreate = (): void => {
    navigation.navigateTo(LocationGemScreens.FORM, { location_gem_id: null });
  };

  const handleImported = (): void => {
    setAnnouncement(LocationGemImportCopy.SuccessAnnouncement);
    refreshFirstPage();
  };

  const handleImport = useOpenLocationGemImportSidePeek(handleImported);

  const {
    rolling,
    error: rollAllError,
    roll_all: rollAll,
  } = useRollAllLocationGems();
  const openBulkRollResult = useOpenLocationGemBulkRollResultSidePeek();

  const handleRollAll = async (): Promise<void> => {
    const result = await rollAll();

    if (result) {
      setAnnouncement(`Rolled ${result.rolled_count} Location Gems.`);
      refreshFirstPage();
      openBulkRollResult(result);
    }
  };

  const totalPages = response?.meta.pagination.total_pages ?? 0;
  const totalRecords = response?.meta.pagination.total ?? 0;

  return (
    <AdminPage
      title="Location Gems"
      width={AdminPageWidth.Standard}
      header_actions={
        <>
          <AdminAnchorButton
            href="/admin"
            label="Back"
            variant={ButtonVariant.DANGER}
          />
          <Button
            label="Create Location Gem"
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
          href="/admin/location-gems/export"
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
            id="location-gem-map-filter"
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
            id="location-gem-location-filter"
            aria_label="Filter by Location"
            searchable
            items={locationItems}
            pre_selected_item={locationItems.find(
              (item) => item.value === locationId
            )}
            on_select={(item) => setLocationId(parseNumberOption(item.value))}
            on_clear={() => setLocationId(null)}
            selection_placeholder="All Locations"
          />
        </div>
      </div>

      {rollAllError && (
        <div className="mb-4">
          <ApiErrorAlert apiError={rollAllError.message} />
        </div>
      )}

      <DataTable<LocationGemListDefinition>
        id_prefix="location-gems"
        caption="Location Gems"
        rows={data}
        row_id={(row) => row.id}
        columns={columns}
        loading={loading}
        error={error?.message ?? null}
        empty_message="No Location Gem profiles match the current search and filters."
        search_label="Search Location Gems"
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

export default LocationGemListScreen;
