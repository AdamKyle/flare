import React, { ReactNode } from 'react';

import AdminAnchorButton from '../../shared/components/admin-anchor-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import GuideQuestListDefinition from '../api/definitions/guide-quest-list-definition';
import { useGuideQuests } from '../api/hooks/use-guide-quests';
import { GuideQuestScreens } from '../screen-manager/guide-quest-screen-constants';
import { useGuideQuestScreenNavigation } from '../screen-manager/guide-quest-screen-kit';
import { buildGuideQuestListColumns } from '../utils/build-guide-quest-list-columns';

import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import DataTable from 'ui/data-table/data-table';

const GuideQuestListScreen = (): ReactNode => {
  const navigation = useGuideQuestScreenNavigation();
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
  } = useGuideQuests();

  const handleRowActivate = (row: GuideQuestListDefinition): void => {
    navigation.navigateTo(GuideQuestScreens.SHOW, { guide_quest_id: row.id });
  };

  return (
    <AdminPage
      title="Guide Quests"
      width={AdminPageWidth.Standard}
      header_actions={
        <>
          <AdminAnchorButton
            href="/admin"
            label="Back"
            variant={ButtonVariant.DANGER}
          />
          <AdminAnchorButton
            href="/admin/guide-quests/create"
            label="Create Guide Quest"
            variant={ButtonVariant.PRIMARY}
          />
        </>
      }
    >
      <div className="mb-4 flex flex-wrap items-center gap-3">
        <AdminAnchorButton
          href="/admin/guide-quests/import"
          label="Import"
          variant={ButtonVariant.PRIMARY}
        />
        <AdminAnchorButton
          href="/admin/guide-quests/export"
          label="Export"
          variant={ButtonVariant.PRIMARY}
        />
      </div>
      <DataTable<GuideQuestListDefinition>
        id_prefix="guide-quests"
        caption="Guide Quests"
        rows={data}
        row_id={(row) => row.id}
        columns={buildGuideQuestListColumns()}
        loading={loading}
        error={error?.message ?? null}
        empty_message="No Guide Quests match the current search."
        search_label="Search Guide Quests"
        search_value={searchText}
        on_search_change={setSearchText}
        current_page={page}
        total_pages={response?.meta.pagination.total_pages ?? 0}
        total_records={response?.meta.pagination.total ?? 0}
        on_page_change={setPage}
        sort_key={sortKey}
        sort_direction={sortDirection}
        on_sort_change={setSort}
        on_row_activate={handleRowActivate}
      />
    </AdminPage>
  );
};

export default GuideQuestListScreen;
