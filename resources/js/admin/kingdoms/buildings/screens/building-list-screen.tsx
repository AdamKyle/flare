import React, { ReactNode, useState } from 'react';

import AdminAnchorButton from '../../../shared/components/admin-anchor-button';
import AdminPage from '../../../shared/components/admin-page';
import { AdminPageWidth } from '../../../shared/enums/admin-page-width';
import KingdomWorkbookActions from '../../shared/components/kingdom-workbook-actions';
import { KingdomImportCopy } from '../../shared/enums/kingdom-import-copy';
import { KingdomWebUrls } from '../../shared/enums/kingdom-web-urls';
import BuildingListDefinition from '../api/definitions/building-list-definition';
import { useBuildings } from '../api/hooks/use-buildings';
import { BuildingScreens } from '../screen-manager/building-screen-constants';
import { useBuildingScreenNavigation } from '../screen-manager/building-screen-kit';
import { buildBuildingListColumns } from '../utils/build-building-list-columns';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import DataTable from 'ui/data-table/data-table';

const BuildingListScreen = (): ReactNode => {
  const navigation = useBuildingScreenNavigation();
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
  } = useBuildings();

  const columns = buildBuildingListColumns();

  const handleRowActivate = (row: BuildingListDefinition): void => {
    navigation.navigateTo(BuildingScreens.BUILDING_SHOW, {
      building_id: row.id,
    });
  };

  const handleCreate = (): void => {
    navigation.navigateTo(BuildingScreens.BUILDING_FORM, { building_id: null });
  };

  const handleImported = (): void => {
    setAnnouncement(KingdomImportCopy.SuccessAnnouncement);
    refreshFirstPage();
  };

  const totalPages = response?.meta.pagination.total_pages ?? 0;
  const totalRecords = response?.meta.pagination.total ?? 0;

  return (
    <AdminPage
      title="Kingdom Buildings"
      width={AdminPageWidth.Standard}
      header_actions={
        <>
          <AdminAnchorButton
            href={KingdomWebUrls.ADMIN_HOME}
            label="Back"
            variant={ButtonVariant.DANGER}
          />
          <Button
            label="Create Building"
            variant={ButtonVariant.PRIMARY}
            on_click={handleCreate}
          />
        </>
      }
    >
      <KingdomWorkbookActions on_imported={handleImported} />
      <DataTable<BuildingListDefinition>
        id_prefix="kingdom-buildings"
        caption="Kingdom Buildings"
        rows={data}
        row_id={(row) => row.id}
        columns={columns}
        loading={loading}
        error={error?.message ?? null}
        empty_message="No Buildings match the current search."
        search_label="Search Buildings"
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

export default BuildingListScreen;
