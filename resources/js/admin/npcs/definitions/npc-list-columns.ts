import NpcListDefinition from '../api/definitions/npc-list-definition';
import { renderNpcNameCell } from '../components/npc-list-cells';
import { NPC_TYPE_LABELS } from '../enums/npc-type';

import DataTableColumnDefinition from 'ui/data-table/types/data-table-column-definition';

export const NPC_LIST_COLUMNS: DataTableColumnDefinition<NpcListDefinition>[] =
  [
    {
      key: 'real_name',
      header: 'Name',
      sortable: true,
      sort_key: 'real_name',
      value: renderNpcNameCell,
    },
    {
      key: 'type',
      header: 'Type',
      sortable: true,
      sort_key: 'type',
      value: (row) => NPC_TYPE_LABELS[row.type],
    },
    {
      key: 'map_name',
      header: 'Map',
      value: (row) => row.map_name ?? '—',
    },
    {
      key: 'x_position',
      header: 'X',
      sortable: true,
      sort_key: 'x_position',
      value: (row) => row.x_position,
    },
    {
      key: 'y_position',
      header: 'Y',
      sortable: true,
      sort_key: 'y_position',
      value: (row) => row.y_position,
    },
  ];
