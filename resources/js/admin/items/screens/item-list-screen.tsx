import React, { ReactNode, useState } from 'react';

import ItemDefinition from '../api/definitions/item-definition';
import { useItems } from '../api/hooks/use-items';
import { ItemImportCopy } from '../enums/item-import-copy';
import {
  ITEM_PROFILE_LABELS,
  ITEM_PROFILE_VALUES,
  isItemProfile,
} from '../enums/item-profile';
import { ItemScreens } from '../screen-manager/item-screen-constants';
import { useItemScreenNavigation } from '../screen-manager/item-screen-kit';
import { buildItemListColumns } from '../utils/build-item-list-columns';

import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';

import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import DataTable from 'ui/data-table/data-table';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const PROFILE_ITEMS: DropdownItem[] = ITEM_PROFILE_VALUES.map((profile) => ({
  label: ITEM_PROFILE_LABELS[profile],
  value: profile,
}));

const ItemListScreen = (): ReactNode => {
  const navigation = useItemScreenNavigation();
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
    profile,
    set_profile: setProfile,
    sort_key: sortKey,
    sort_direction: sortDirection,
    set_sort: setSort,
    refresh_first_page: refreshFirstPage,
  } = useItems();

  const handleRowActivate = (item: ItemDefinition): void => {
    navigation.navigateTo(ItemScreens.SHOW, { item_id: item.id });
  };

  const handleCreate = (): void => {
    navigation.navigateTo(ItemScreens.FORM, { item_id: null });
  };

  const handleImported = (): void => {
    setAnnouncement(ItemImportCopy.SuccessAnnouncement);
    refreshFirstPage();
  };

  const handleImport = (): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_ITEM_IMPORT,
      {
        is_open: true,
        title: ItemImportCopy.Title,
        allow_clicking_outside: true,
        on_imported: handleImported,
      }
    );
  };

  const totalPages = response?.meta.pagination.total_pages ?? 0;
  const totalRecords = response?.meta.pagination.total ?? 0;
  const columns = buildItemListColumns(profile);

  return (
    <AdminPage
      title="Items"
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
      <div className="mb-4 flex flex-wrap items-center gap-3">
        <div className="w-full max-w-xs">
          <Dropdown
            id="item-profile-select"
            aria_label="Item profile"
            items={PROFILE_ITEMS}
            pre_selected_item={PROFILE_ITEMS.find(
              (item) => item.value === profile
            )}
            on_select={(item) => {
              if (isItemProfile(item.value)) {
                setProfile(item.value);
              }
            }}
            selection_placeholder="Select an Item profile"
          />
        </div>
        <Button
          label={ItemImportCopy.Import}
          variant={ButtonVariant.PRIMARY}
          on_click={handleImport}
        />
        <a
          href={`/admin/items/export?profile=${profile}`}
          className="focus-visible:ring-glacier-400 border-glacier-300 text-glacier-700 hover:bg-glacier-50 dark:border-glacier-700 dark:bg-glacier-950 dark:text-glacier-200 dark:hover:bg-glacier-900 rounded-md border bg-white px-3 py-1.5 text-sm font-medium focus:outline-none focus-visible:ring-2"
        >
          Export
        </a>
      </div>
      <DataTable<ItemDefinition>
        id_prefix="items"
        caption="Items"
        rows={data}
        row_id={(row) => row.id}
        columns={columns}
        loading={loading}
        error={error?.message ?? null}
        empty_message="No Items match the current search."
        search_label="Search Items"
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

export default ItemListScreen;
