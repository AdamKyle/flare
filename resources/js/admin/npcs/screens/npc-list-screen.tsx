import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useEffect, useRef, useState } from 'react';

import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import { useAdminGameMapFilterOptions } from '../../shared/api/hooks/use-admin-game-map-filter-options';
import AdminAnchorButton from '../../shared/components/admin-anchor-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import NpcListDefinition from '../api/definitions/npc-list-definition';
import { useNpcs } from '../api/hooks/use-npcs';
import { NPC_LIST_COLUMNS } from '../definitions/npc-list-columns';
import { NpcImportCopy } from '../enums/npc-import-copy';
import { isNpcType, NPC_TYPE_LABELS, NPC_TYPE_VALUES } from '../enums/npc-type';
import { NpcScreens } from '../screen-manager/npc-screen-constants';
import { useNpcScreenNavigation } from '../screen-manager/npc-screen-kit';
import { parseNumberOption } from '../utils/parse-npc-dropdown-value';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import DataTable from 'ui/data-table/data-table';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const NpcListScreen = (): ReactNode => {
  const navigation = useNpcScreenNavigation();
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
  } = useNpcs();

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

  const typeItems: DropdownItem[] = NPC_TYPE_VALUES.map((npcType) => ({
    label: NPC_TYPE_LABELS[npcType],
    value: npcType,
  }));

  const handleRowActivate = (npc: NpcListDefinition): void => {
    navigation.navigateTo(NpcScreens.SHOW, { npc_id: npc.id });
  };

  const handleCreate = (): void => {
    navigation.navigateTo(NpcScreens.FORM, { game_map_id: null });
  };

  const handleImported = (): void => {
    setAnnouncement(NpcImportCopy.SuccessAnnouncement);
    refreshFirstPage();
  };

  const handleImport = (): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_NPC_IMPORT,
      {
        is_open: true,
        title: NpcImportCopy.Title,
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
        aria-label="NPC utilities"
      >
        <div className="w-full sm:max-w-xs">
          <Dropdown
            id="npc-map-filter"
            aria_label="Filter NPCs by Game Map"
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
            id="npc-type-filter"
            aria_label="Filter NPCs by Type"
            items={typeItems}
            pre_selected_item={typeItems.find((item) => item.value === type)}
            on_select={(item) => {
              if (isNpcType(item.value)) {
                setType(item.value);
              }
            }}
            on_clear={() => setType(null)}
            selection_placeholder="All Types"
          />
        </div>

        <Button
          label={NpcImportCopy.Import}
          variant={ButtonVariant.PRIMARY}
          on_click={handleImport}
        />
        <AdminAnchorButton
          href="/admin/npcs/export"
          label="Export"
          variant={ButtonVariant.PRIMARY}
        />
      </div>
    );
  };

  return (
    <AdminPage
      title="NPCs"
      width={AdminPageWidth.Standard}
      header_actions={
        <>
          <AdminAnchorButton
            href="/admin"
            label="Back"
            variant={ButtonVariant.DANGER}
          />
          <Button
            label="Create"
            variant={ButtonVariant.PRIMARY}
            on_click={handleCreate}
          />
        </>
      }
    >
      {renderFilters()}
      <DataTable<NpcListDefinition>
        id_prefix="npcs"
        caption="NPCs"
        rows={data}
        row_id={(row) => row.id}
        columns={NPC_LIST_COLUMNS}
        loading={loading}
        error={error?.message ?? null}
        empty_message="No NPCs match the current search and filters."
        search_label="Search NPCs"
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

export default NpcListScreen;
