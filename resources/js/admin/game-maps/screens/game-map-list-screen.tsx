import React, { ReactNode, useState } from 'react';

import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import AdminAnchorButton from '../../shared/components/admin-anchor-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import GameMapDefinition from '../api/definitions/game-map-definition';
import { useGameMaps } from '../api/hooks/use-game-maps';
import { GAME_MAP_LIST_COLUMNS } from '../definitions/game-map-list-columns';
import { GameMapImportCopy } from '../enums/game-map-import-copy';
import { GameMapScreens } from '../screen-manager/game-map-screen-constants';
import { useGameMapScreenNavigation } from '../screen-manager/game-map-screen-kit';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import DataTable from 'ui/data-table/data-table';

const GameMapListScreen = (): ReactNode => {
  const navigation = useGameMapScreenNavigation();
  const sidePeekEmitter = useSidePeekEmitter();
  const [announcement, setAnnouncement] = useState('');
  const {
    data,
    loading,
    error,
    response,
    search_text: searchText,
    set_search_text: setSearchText,
    page,
    set_page: setPage,
    sort_key: sortKey,
    sort_direction: sortDirection,
    set_sort: setSort,
    refresh_first_page: refreshFirstPage,
  } = useGameMaps();

  const handleRowActivate = (gameMap: GameMapDefinition): void => {
    navigation.navigateTo(GameMapScreens.SHOW, { game_map_id: gameMap.id });
  };

  const handleCreate = (): void => {
    navigation.navigateTo(GameMapScreens.FORM, {});
  };

  const handleImported = (): void => {
    setAnnouncement(GameMapImportCopy.SuccessAnnouncement);
    refreshFirstPage();
  };

  const handleImport = (): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_IMPORT,
      {
        is_open: true,
        title: GameMapImportCopy.Title,
        allow_clicking_outside: true,
        on_imported: handleImported,
      }
    );
  };

  const totalPages = response?.meta.pagination.total_pages ?? 0;
  const totalRecords = response?.meta.pagination.total ?? 0;

  return (
    <AdminPage
      title="Game Maps"
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
      <div
        className="mb-4 flex flex-wrap items-center gap-3"
        aria-label="Game Map utilities"
      >
        <Button
          label={GameMapImportCopy.Import}
          variant={ButtonVariant.PRIMARY}
          on_click={handleImport}
        />
        <AdminAnchorButton
          href="/admin/game-maps/export"
          label="Export"
          variant={ButtonVariant.PRIMARY}
        />
      </div>
      <DataTable<GameMapDefinition>
        id_prefix="game-maps"
        caption="Game Maps"
        rows={data}
        row_id={(row) => row.id}
        columns={GAME_MAP_LIST_COLUMNS}
        loading={loading}
        error={error?.message ?? null}
        empty_message="No Game Maps match the current search."
        search_label="Search Game Maps"
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

export default GameMapListScreen;
