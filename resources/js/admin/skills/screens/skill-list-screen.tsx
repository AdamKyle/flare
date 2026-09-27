import React, { ReactNode, useState } from 'react';

import AdminAnchorButton from '../../shared/components/admin-anchor-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import SkillListDefinition from '../api/definitions/skill-list-definition';
import { useSkills } from '../api/hooks/use-skills';
import { SkillImportCopy } from '../enums/skill-import-copy';
import { SkillWebUrls } from '../enums/skill-web-urls';
import { useOpenSkillImportSidePeek } from '../hooks/use-open-skill-import-side-peek';
import { SkillScreens } from '../screen-manager/skill-screen-constants';
import { useSkillScreenNavigation } from '../screen-manager/skill-screen-kit';
import { buildSkillListColumns } from '../utils/build-skill-list-columns';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import DataTable from 'ui/data-table/data-table';

const SkillListScreen = (): ReactNode => {
  const navigation = useSkillScreenNavigation();
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
  } = useSkills();

  const columns = buildSkillListColumns();

  const handleRowActivate = (row: SkillListDefinition): void => {
    navigation.navigateTo(SkillScreens.SHOW, { skill_id: row.id });
  };

  const handleCreate = (): void => {
    navigation.navigateTo(SkillScreens.FORM, { skill_id: null });
  };

  const handleImported = (): void => {
    setAnnouncement(SkillImportCopy.SuccessAnnouncement);
    refreshFirstPage();
  };

  const handleImport = useOpenSkillImportSidePeek(handleImported);

  const totalPages = response?.meta.pagination.total_pages ?? 0;
  const totalRecords = response?.meta.pagination.total ?? 0;

  return (
    <AdminPage
      title="Skills"
      width={AdminPageWidth.Standard}
      header_actions={
        <>
          <AdminAnchorButton
            href={SkillWebUrls.ADMIN_HOME}
            label="Back"
            variant={ButtonVariant.DANGER}
          />
          <Button
            label="Create Skill"
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
          href={SkillWebUrls.EXPORT}
          label="Export"
          variant={ButtonVariant.PRIMARY}
        />
      </div>
      <DataTable<SkillListDefinition>
        id_prefix="skills"
        caption="Skills"
        rows={data}
        row_id={(row) => row.id}
        columns={columns}
        loading={loading}
        error={error?.message ?? null}
        empty_message="No Skills match the current search."
        search_label="Search Skills"
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

export default SkillListScreen;
