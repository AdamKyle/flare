import GuideQuestListDefinition from '../api/definitions/guide-quest-list-definition';

import DataTableColumnDefinition from 'ui/data-table/types/data-table-column-definition';

const optionalNumber = (value: number | null): string | number => value ?? '';

export const buildGuideQuestListColumns = (): Array<
  DataTableColumnDefinition<GuideQuestListDefinition>
> => [
  {
    key: 'name',
    header: 'Name',
    value: (row) => row.name,
    sortable: true,
    sort_key: 'name',
  },
  {
    key: 'required_level',
    header: 'Required Level',
    value: (row) => optionalNumber(row.required_level),
    sortable: true,
    sort_key: 'required_level',
  },
  {
    key: 'unlock_at_level',
    header: 'Unlock At Level',
    value: (row) => optionalNumber(row.unlock_at_level),
    sortable: true,
    sort_key: 'unlock_at_level',
  },
  { key: 'parent', header: 'Parent', value: (row) => row.parent?.name ?? '' },
  { key: 'event', header: 'Event', value: (row) => row.event?.label ?? '' },
];
