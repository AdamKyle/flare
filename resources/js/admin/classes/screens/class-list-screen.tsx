import React, { ReactNode, useState } from 'react';

import AdminAnchorButton from '../../shared/components/admin-anchor-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import ClassListDefinition from '../api/definitions/class-list-definition';
import { useClasses } from '../api/hooks/use-classes';
import { ClassImportCopy } from '../enums/class-import-copy';
import { useOpenClassImportSidePeek } from '../hooks/use-open-class-import-side-peek';
import { ClassScreens } from '../screen-manager/class-screen-constants';
import { useClassScreenNavigation } from '../screen-manager/class-screen-kit';
import { buildClassListColumns } from '../utils/build-class-list-columns';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import DataTable from 'ui/data-table/data-table';

const ClassListScreen = (): ReactNode => {
  const navigation = useClassScreenNavigation();
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
  } = useClasses();

  const columns = buildClassListColumns();

  const handleRowActivate = (row: ClassListDefinition): void => {
    navigation.navigateTo(ClassScreens.SHOW, { class_id: row.id });
  };

  const handleCreate = (): void => {
    navigation.navigateTo(ClassScreens.FORM, { class_id: null });
  };

  const handleImported = (): void => {
    setAnnouncement(ClassImportCopy.SuccessAnnouncement);
    refreshFirstPage();
  };
  const handleImport = useOpenClassImportSidePeek(handleImported);

  const totalPages = response?.meta.pagination.total_pages ?? 0;
  const totalRecords = response?.meta.pagination.total ?? 0;

  return (
    <AdminPage
      title="Classes"
      width={AdminPageWidth.Standard}
      header_actions={
        <>
          <AdminAnchorButton
            href="/admin"
            label="Back"
            variant={ButtonVariant.DANGER}
          />
          <Button
            label="Create Class"
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
          href="/admin/classes/export"
          label="Export"
          variant={ButtonVariant.PRIMARY}
        />
      </div>
      <DataTable<ClassListDefinition>
        id_prefix="classes"
        caption="Classes"
        rows={data}
        row_id={(row) => row.id}
        columns={columns}
        loading={loading}
        error={error?.message ?? null}
        empty_message="No Classes match the current search."
        search_label="Search Classes"
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

export default ClassListScreen;
