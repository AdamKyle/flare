import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useEffect, useRef, useState } from 'react';

import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import { useAdminGameMapFilterOptions } from '../../shared/api/hooks/use-admin-game-map-filter-options';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import LocationListDefinition from '../api/definitions/location-list-definition';
import { useLocations } from '../api/hooks/use-locations';
import { LOCATION_LIST_COLUMNS } from '../definitions/location-list-columns';
import { LocationImportCopy } from '../enums/location-import-copy';
import {
  isLocationType,
  LOCATION_TYPE_LABELS,
  LOCATION_TYPE_VALUES,
} from '../enums/location-type';
import { LocationScreens } from '../screen-manager/location-screen-constants';
import { useLocationScreenNavigation } from '../screen-manager/location-screen-kit';
import { parseNumberOption } from '../utils/parse-location-dropdown-value';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import DataTable from 'ui/data-table/data-table';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const LocationListScreen = (): ReactNode => {
  const navigation = useLocationScreenNavigation();
  const sidePeekEmitter = useSidePeekEmitter();
  const [announcement, setAnnouncement] = useState('');
  const hasInitializedMapRef = useRef(false);

  const {
    options: mapOptions,
    loading: mapOptionsLoading,
    error: mapOptionsError,
  } = useAdminGameMapFilterOptions();

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
    type,
    set_type: setType,
    sort_key: sortKey,
    sort_direction: sortDirection,
    set_sort: setSort,
    refresh_first_page: refreshFirstPage,
  } = useLocations();

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

  const typeItems: DropdownItem[] = LOCATION_TYPE_VALUES.map(
    (locationType) => ({
      label: LOCATION_TYPE_LABELS[locationType],
      value: locationType,
    })
  );

  const handleRowActivate = (location: LocationListDefinition): void => {
    navigation.navigateTo(LocationScreens.SHOW, { location_id: location.id });
  };

  const handleCreate = (): void => {
    navigation.navigateTo(LocationScreens.FORM, { game_map_id: null });
  };

  const handleImported = (): void => {
    setAnnouncement(LocationImportCopy.SuccessAnnouncement);
    refreshFirstPage();
  };

  const handleImport = (): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_LOCATION_IMPORT,
      {
        is_open: true,
        title: LocationImportCopy.Title,
        allow_clicking_outside: true,
        on_imported: handleImported,
      }
    );
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
      <div
        className="mb-4 flex flex-wrap items-center gap-3"
        aria-label="Location utilities"
      >
        <div className="w-full sm:max-w-xs">
          <Dropdown
            id="location-map-filter"
            aria_label="Filter Locations by Game Map"
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

        <div className="w-full sm:max-w-xs">
          <Dropdown
            id="location-type-filter"
            aria_label="Filter Locations by Type"
            searchable
            items={typeItems}
            pre_selected_item={typeItems.find((item) => item.value === type)}
            on_select={(item) => {
              if (isLocationType(item.value)) {
                setType(item.value);
              }
            }}
            on_clear={() => setType(null)}
            selection_placeholder="All Types"
          />
        </div>

        <Button
          label={LocationImportCopy.Import}
          variant={ButtonVariant.PRIMARY}
          on_click={handleImport}
        />
        <a
          href="/admin/locations/export"
          className="focus-visible:ring-glacier-400 border-glacier-300 text-glacier-700 hover:bg-glacier-50 dark:border-glacier-700 dark:bg-glacier-950 dark:text-glacier-200 dark:hover:bg-glacier-900 rounded-md border bg-white px-3 py-1.5 text-sm font-medium focus:outline-none focus-visible:ring-2"
        >
          Export
        </a>
      </div>
    );
  };

  return (
    <AdminPage
      title="Locations"
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
            label="Create"
            variant={ButtonVariant.PRIMARY}
            on_click={handleCreate}
          />
        </>
      }
    >
      {renderFilters()}
      <DataTable<LocationListDefinition>
        id_prefix="locations"
        caption="Locations"
        rows={data}
        row_id={(row) => row.id}
        columns={LOCATION_LIST_COLUMNS}
        loading={loading}
        error={error?.message ?? null}
        empty_message="No Locations match the current search and filters."
        search_label="Search Locations"
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

export default LocationListScreen;
