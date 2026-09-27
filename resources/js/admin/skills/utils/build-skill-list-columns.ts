import SkillListDefinition from '../api/definitions/skill-list-definition';
import { skillTypeLabel } from '../enums/skill-type';

import DataTableColumnDefinition from 'ui/data-table/types/data-table-column-definition';

export const buildSkillListColumns =
  (): DataTableColumnDefinition<SkillListDefinition>[] => [
    {
      key: 'name',
      header: 'Name',
      value: (row) => row.name,
      sortable: true,
      sort_key: 'name',
    },
    {
      key: 'type',
      header: 'Type',
      value: (row) => skillTypeLabel(row.type),
      sortable: true,
      sort_key: 'type',
    },
    {
      key: 'max_level',
      header: 'Max Level',
      value: (row) => row.max_level,
      sortable: true,
      sort_key: 'max_level',
    },
    {
      key: 'can_train',
      header: 'Trainable',
      value: (row) => (row.can_train ? 'Yes' : 'No'),
      sortable: true,
      sort_key: 'can_train',
    },
    {
      key: 'is_locked',
      header: 'Locked',
      value: (row) => (row.is_locked ? 'Yes' : 'No'),
      sortable: true,
      sort_key: 'is_locked',
    },
    {
      key: 'game_class',
      header: 'Class',
      value: (row) => row.game_class?.name ?? 'None',
    },
  ];
