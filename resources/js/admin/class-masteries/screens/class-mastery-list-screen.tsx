import React, { ReactNode, useState } from 'react';

import AdminAnchorButton from '../../shared/components/admin-anchor-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import ClassMasteryListDefinition from '../api/definitions/class-mastery-list-definition';
import { useClassMasteries } from '../api/hooks/use-class-masteries';
import { useClassMasteryFormOptions } from '../api/hooks/use-class-mastery-form-options';
import { ClassMasteryImportCopy } from '../enums/class-mastery-import-copy';
import { useOpenClassMasteryImportSidePeek } from '../hooks/use-open-class-mastery-import-side-peek';
import { ClassMasteryScreens } from '../screen-manager/class-mastery-screen-constants';
import { useClassMasteryScreenNavigation } from '../screen-manager/class-mastery-screen-kit';
import { parseNumberOption } from '../utils/parse-class-mastery-dropdown-value';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import DataTable from 'ui/data-table/data-table';
import DataTableColumnDefinition from 'ui/data-table/types/data-table-column-definition';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const ClassMasteryListScreen = (): ReactNode => {
  const navigation = useClassMasteryScreenNavigation();
  const [announcement, setAnnouncement] = useState('');
  const { form_options: formOptions } = useClassMasteryFormOptions();

  const {
    data,
    loading,
    error,
    response,
    search_text: searchText,
    set_search_text: setSearchText,
    page,
    set_page: setPage,
    game_class_id: gameClassId,
    set_game_class_id: setGameClassId,
    sort_key: sortKey,
    sort_direction: sortDirection,
    set_sort: setSort,
    refresh_first_page: refreshFirstPage,
  } = useClassMasteries();

  const classItems: DropdownItem[] = (formOptions?.classes ?? []).map(
    (gameClass) => ({
      label: gameClass.name,
      value: gameClass.id,
    })
  );

  const columns: DataTableColumnDefinition<ClassMasteryListDefinition>[] = [
    {
      key: 'name',
      header: 'Name',
      value: (row) => row.name,
      sortable: true,
      sort_key: 'name',
    },
    {
      key: 'game_class',
      header: 'Class',
      value: (row) => row.game_class.name,
    },
    {
      key: 'type',
      header: 'Type',
      value: (row) => (row.type === 'attack' ? 'Attack' : 'Passive'),
    },
    {
      key: 'requires_class_rank_level',
      header: 'Required Rank',
      value: (row) => String(row.requires_class_rank_level),
      sortable: true,
      sort_key: 'requires_class_rank_level',
    },
  ];

  const handleRowActivate = (row: ClassMasteryListDefinition): void => {
    navigation.navigateTo(ClassMasteryScreens.SHOW, {
      class_mastery_id: row.id,
    });
  };

  const handleCreate = (): void => {
    navigation.navigateTo(ClassMasteryScreens.FORM, {
      class_mastery_id: null,
    });
  };

  const handleImported = (): void => {
    setAnnouncement(ClassMasteryImportCopy.SuccessAnnouncement);
    refreshFirstPage();
  };

  const handleImport = useOpenClassMasteryImportSidePeek(handleImported);

  const totalPages = response?.meta.pagination.total_pages ?? 0;
  const totalRecords = response?.meta.pagination.total ?? 0;

  return (
    <AdminPage
      title="Class Masteries"
      width={AdminPageWidth.Standard}
      header_actions={
        <>
          <AdminAnchorButton
            href="/admin"
            label="Back"
            variant={ButtonVariant.DANGER}
          />
          <Button
            label="Create Class Mastery"
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
          href="/admin/class-masteries/export"
          label="Export"
          variant={ButtonVariant.PRIMARY}
        />
        <div className="w-full max-w-xs">
          <Dropdown
            id="class-mastery-class-filter"
            aria_label="Filter by Class"
            searchable
            items={classItems}
            pre_selected_item={classItems.find(
              (item) => item.value === gameClassId
            )}
            on_select={(item) => setGameClassId(parseNumberOption(item.value))}
            on_clear={() => setGameClassId(null)}
            selection_placeholder="All Classes"
          />
        </div>
      </div>

      <DataTable<ClassMasteryListDefinition>
        id_prefix="class-masteries"
        caption="Class Masteries"
        rows={data}
        row_id={(row) => row.id}
        columns={columns}
        loading={loading}
        error={error?.message ?? null}
        empty_message="No Class Masteries match the current search and filters."
        search_label="Search Class Masteries"
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

export default ClassMasteryListScreen;
