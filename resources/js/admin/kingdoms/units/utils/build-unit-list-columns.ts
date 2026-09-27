import UnitListDefinition from '../api/definitions/unit-list-definition';

import DataTableColumnDefinition from 'ui/data-table/types/data-table-column-definition';

export const buildUnitListColumns =
  (): DataTableColumnDefinition<UnitListDefinition>[] => [
    {
      key: 'name',
      header: 'Name',
      value: (row) => row.name,
      sortable: true,
      sort_key: 'name',
    },
    {
      key: 'attack',
      header: 'Attack',
      value: (row) => row.attack,
      sortable: true,
      sort_key: 'attack',
    },
    {
      key: 'defence',
      header: 'Defence',
      value: (row) => row.defence,
      sortable: true,
      sort_key: 'defence',
    },
    {
      key: 'time_to_recruit',
      header: 'Time to Recruit',
      value: (row) => row.time_to_recruit,
      sortable: true,
      sort_key: 'time_to_recruit',
    },
    {
      key: 'is_special',
      header: 'Special',
      value: (row) => (row.is_special ? 'Yes' : 'No'),
      sortable: true,
      sort_key: 'is_special',
    },
  ];
