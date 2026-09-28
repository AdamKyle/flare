import React, { ReactNode } from 'react';

import { gemAbilityEffectTypeLabel } from '../../../game/reusable-components/gem-ability/enums/gem-ability-effect-type';
import {
  GemAbilityType,
  gemAbilityTypeLabel,
} from '../../../game/reusable-components/gem-ability/enums/gem-ability-type';
import AdminAnchorButton from '../../shared/components/admin-anchor-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import GemAbilityListDefinition from '../api/definitions/gem-ability-list-definition';
import { useGemAbilities } from '../api/hooks/use-gem-abilities';
import { GemAbilityScreens } from '../screen-manager/gem-ability-screen-constants';
import { useGemAbilityScreenNavigation } from '../screen-manager/gem-ability-screen-kit';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import DataTable from 'ui/data-table/data-table';
import DataTableColumnDefinition from 'ui/data-table/types/data-table-column-definition';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const ABILITY_TYPE_ITEMS: DropdownItem[] = Object.values(GemAbilityType).map(
  (abilityType) => ({
    label: gemAbilityTypeLabel(abilityType),
    value: abilityType,
  })
);

const COLUMNS: DataTableColumnDefinition<GemAbilityListDefinition>[] = [
  {
    key: 'name',
    header: 'Name',
    value: (row) => row.name,
    sortable: true,
    sort_key: 'name',
  },
  {
    key: 'ability_type',
    header: 'Ability Type',
    value: (row) => gemAbilityTypeLabel(row.ability_type),
    sortable: true,
    sort_key: 'ability_type',
  },
  {
    key: 'effect_type',
    header: 'Effect',
    value: (row) => gemAbilityEffectTypeLabel(row.effect_type),
    sortable: true,
    sort_key: 'effect_type',
  },
  {
    key: 'enabled',
    header: 'Enabled',
    value: (row) => (row.enabled ? 'Yes' : 'No'),
    sortable: true,
    sort_key: 'enabled',
  },
];

const GemAbilityListScreen = (): ReactNode => {
  const navigation = useGemAbilityScreenNavigation();

  const {
    data,
    loading,
    error,
    response,
    search_text: searchText,
    set_search_text: setSearchText,
    page,
    set_page: setPage,
    ability_type: abilityType,
    set_ability_type: setAbilityType,
    sort_key: sortKey,
    sort_direction: sortDirection,
    set_sort: setSort,
  } = useGemAbilities();

  const totalPages = response?.meta.pagination.total_pages ?? 0;
  const totalRecords = response?.meta.pagination.total ?? 0;

  const handleRowActivate = (row: GemAbilityListDefinition): void => {
    navigation.navigateTo(GemAbilityScreens.SHOW, {
      gem_ability_id: row.id,
    });
  };

  const handleCreate = (): void => {
    navigation.navigateTo(GemAbilityScreens.FORM, {
      gem_ability_id: null,
    });
  };

  const handleAbilityTypeSelect = (item: DropdownItem): void => {
    setAbilityType(
      Object.values(GemAbilityType).find(
        (candidate) => candidate === item.value
      ) ?? null
    );
  };

  const handleAbilityTypeClear = (): void => {
    setAbilityType(null);
  };

  return (
    <AdminPage
      title="Gem Abilities"
      width={AdminPageWidth.Standard}
      header_actions={
        <>
          <AdminAnchorButton
            href="/admin"
            label="Back"
            variant={ButtonVariant.DANGER}
          />
          <Button
            label="Create Gem Ability"
            variant={ButtonVariant.PRIMARY}
            on_click={handleCreate}
          />
        </>
      }
    >
      <div className="mb-4 flex flex-wrap items-center gap-3">
        <div className="w-full max-w-xs">
          <Dropdown
            id="gem-ability-type-filter"
            aria_label="Filter by Ability Type"
            items={ABILITY_TYPE_ITEMS}
            pre_selected_item={ABILITY_TYPE_ITEMS.find(
              (item) => item.value === abilityType
            )}
            on_select={handleAbilityTypeSelect}
            on_clear={handleAbilityTypeClear}
            selection_placeholder="All Ability Types"
          />
        </div>
      </div>

      <DataTable<GemAbilityListDefinition>
        id_prefix="gem-abilities"
        caption="Gem Abilities"
        rows={data}
        row_id={(row) => row.id}
        columns={COLUMNS}
        loading={loading}
        error={error?.message ?? null}
        empty_message="No Gem Abilities match the current search and filters."
        search_label="Search Gem Abilities"
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
    </AdminPage>
  );
};

export default GemAbilityListScreen;
