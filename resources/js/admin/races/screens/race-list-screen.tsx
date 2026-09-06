import React, { ReactNode, useState } from 'react';

import AdminAnchorButton from '../../shared/components/admin-anchor-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import RaceDefinition from '../api/definitions/race-definition';
import { useRaces } from '../api/hooks/use-races';
import { RaceImportCopy } from '../enums/race-import-copy';
import { useOpenRaceImportSidePeek } from '../hooks/use-open-race-import-side-peek';
import { RaceScreens } from '../screen-manager/race-screen-constants';
import { useRaceScreenNavigation } from '../screen-manager/race-screen-kit';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import DataTable from 'ui/data-table/data-table';
import DataTableColumnDefinition from 'ui/data-table/types/data-table-column-definition';

const RaceListScreen = (): ReactNode => {
  const navigation = useRaceScreenNavigation();
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
  } = useRaces();

  const columns: DataTableColumnDefinition<RaceDefinition>[] = [
    {
      key: 'photo',
      header: 'Photo',
      value: (row) => (
        <img
          src={row.image_url}
          alt={`${row.name} portrait`}
          className="h-10 w-10 rounded-full object-cover"
        />
      ),
      class_name: 'w-16',
    },
    {
      key: 'name',
      header: 'Name',
      value: (row) => row.name,
      sortable: true,
      sort_key: 'name',
    },
    {
      key: 'description',
      header: 'Description',
      value: (row) => row.description ?? '',
    },
  ];

  const handleRowActivate = (row: RaceDefinition): void => {
    navigation.navigateTo(RaceScreens.SHOW, { race_id: row.id });
  };

  const handleCreate = (): void => {
    navigation.navigateTo(RaceScreens.FORM, { race_id: null });
  };

  const handleImported = (): void => {
    setAnnouncement(RaceImportCopy.SuccessAnnouncement);
    refreshFirstPage();
  };

  const handleImport = useOpenRaceImportSidePeek(handleImported);

  const totalPages = response?.meta.pagination.total_pages ?? 0;
  const totalRecords = response?.meta.pagination.total ?? 0;

  return (
    <AdminPage
      title="Races"
      width={AdminPageWidth.Standard}
      header_actions={
        <>
          <AdminAnchorButton
            href="/admin"
            label="Back"
            variant={ButtonVariant.DANGER}
          />
          <Button
            label="Create Race"
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
          href="/admin/races/export"
          label="Export"
          variant={ButtonVariant.PRIMARY}
        />
      </div>
      <DataTable<RaceDefinition>
        id_prefix="races"
        caption="Races"
        rows={data}
        row_id={(row) => row.id}
        columns={columns}
        loading={loading}
        error={error?.message ?? null}
        empty_message="No Races match the current search."
        search_label="Search Races"
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

export default RaceListScreen;
