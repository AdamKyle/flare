import ClassListDefinition from '../api/definitions/class-list-definition';
import { coreStatLabel } from '../enums/core-stat';

import DataTableColumnDefinition from 'ui/data-table/types/data-table-column-definition';

const renderUnlockRequirements = (row: ClassListDefinition): string => {
  if (
    !row.has_unlock_requirements ||
    !row.primary_required_class ||
    !row.secondary_required_class
  ) {
    return 'None';
  }

  return (
    `${row.primary_required_class.name} (Lv.${row.primary_required_class_level}), ` +
    `${row.secondary_required_class.name} (Lv.${row.secondary_required_class_level})`
  );
};

export const buildClassListColumns =
  (): DataTableColumnDefinition<ClassListDefinition>[] => [
    {
      key: 'name',
      header: 'Name',
      value: (row) => row.name,
      sortable: true,
      sort_key: 'name',
    },
    {
      key: 'damage_stat',
      header: 'Damage Stat',
      value: (row) => coreStatLabel(row.damage_stat),
      sortable: true,
      sort_key: 'damage_stat',
    },
    {
      key: 'to_hit_stat',
      header: 'To Hit Stat',
      value: (row) => coreStatLabel(row.to_hit_stat),
      sortable: true,
      sort_key: 'to_hit_stat',
    },
    {
      key: 'unlock_requirements',
      header: 'Unlock Requirements',
      value: (row) => renderUnlockRequirements(row),
    },
  ];
