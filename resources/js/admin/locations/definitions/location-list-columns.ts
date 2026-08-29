import LocationListDefinition from '../api/definitions/location-list-definition';
import { renderLocationNameCell } from '../components/location-list-cells';
import { LOCATION_TYPE_LABELS } from '../enums/location-type';

import DataTableColumnDefinition from 'ui/data-table/types/data-table-column-definition';

export const LOCATION_LIST_COLUMNS: DataTableColumnDefinition<LocationListDefinition>[] =
  [
    {
      key: 'name',
      header: 'Name',
      sortable: true,
      sort_key: 'name',
      value: renderLocationNameCell,
    },
    {
      key: 'map_name',
      header: 'Map',
      value: (row) => row.map_name ?? '—',
    },
    {
      key: 'type',
      header: 'Type',
      sortable: true,
      sort_key: 'type',
      value: (row) =>
        row.type === null ? '—' : LOCATION_TYPE_LABELS[row.type],
    },
    {
      key: 'x',
      header: 'X',
      sortable: true,
      sort_key: 'x',
      value: (row) => row.x,
    },
    {
      key: 'y',
      header: 'Y',
      sortable: true,
      sort_key: 'y',
      value: (row) => row.y,
    },
  ];
