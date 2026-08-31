import React, { ReactNode, useState } from 'react';

import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import NpcListDefinition from '../api/definitions/npc-list-definition';
import { useNpcs } from '../api/hooks/use-npcs';
import { NPC_LIST_COLUMNS } from '../definitions/npc-list-columns';
import { NpcImportCopy } from '../enums/npc-import-copy';
import { NpcScreens } from '../screen-manager/npc-screen-constants';
import { useNpcScreenNavigation } from '../screen-manager/npc-screen-kit';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import DataTable from 'ui/data-table/data-table';

const NpcListScreen = (): ReactNode => {
  const navigation = useNpcScreenNavigation();
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
  } = useNpcs();

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

  return (
    <AdminPage
      title="NPCs"
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
      <div
        className="mb-4 flex flex-wrap items-center gap-3"
        aria-label="NPC utilities"
      >
        <Button
          label={NpcImportCopy.Import}
          variant={ButtonVariant.PRIMARY}
          on_click={handleImport}
        />
        <a
          href="/admin/npcs/export"
          className="focus-visible:ring-glacier-400 border-glacier-300 text-glacier-700 hover:bg-glacier-50 dark:border-glacier-700 dark:bg-glacier-950 dark:text-glacier-200 dark:hover:bg-glacier-900 rounded-md border bg-white px-3 py-1.5 text-sm font-medium focus:outline-none focus-visible:ring-2"
        >
          Export
        </a>
      </div>
      <DataTable<NpcListDefinition>
        id_prefix="npcs"
        caption="NPCs"
        rows={data}
        row_id={(row) => row.id}
        columns={NPC_LIST_COLUMNS}
        loading={loading}
        error={error?.message ?? null}
        empty_message="No NPCs match the current search."
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
