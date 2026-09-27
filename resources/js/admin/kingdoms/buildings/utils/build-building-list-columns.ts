import BuildingListDefinition from '../api/definitions/building-list-definition';

import DataTableColumnDefinition from 'ui/data-table/types/data-table-column-definition';

const formatYesNo = (value: boolean | null): string => (value ? 'Yes' : 'No');

export const buildBuildingListColumns =
  (): DataTableColumnDefinition<BuildingListDefinition>[] => [
    {
      key: 'name',
      header: 'Name',
      value: (row) => row.name,
      sortable: true,
      sort_key: 'name',
    },
    {
      key: 'max_level',
      header: 'Max Level',
      value: (row) => row.max_level,
      sortable: true,
      sort_key: 'max_level',
    },
    {
      key: 'trains_units',
      header: 'Trains Units',
      value: (row) => formatYesNo(row.trains_units),
      sortable: true,
      sort_key: 'trains_units',
    },
    {
      key: 'is_resource_building',
      header: 'Resource Building',
      value: (row) => formatYesNo(row.is_resource_building),
      sortable: true,
      sort_key: 'is_resource_building',
    },
    {
      key: 'is_locked',
      header: 'Locked',
      value: (row) => formatYesNo(row.is_locked),
      sortable: true,
      sort_key: 'is_locked',
    },
    {
      key: 'is_special',
      header: 'Special',
      value: (row) => formatYesNo(row.is_special),
      sortable: true,
      sort_key: 'is_special',
    },
  ];
