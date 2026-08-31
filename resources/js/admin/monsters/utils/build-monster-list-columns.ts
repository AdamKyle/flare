import MonsterListDefinition from '../api/definitions/monster-list-definition';

import DataTableColumnDefinition from 'ui/data-table/types/data-table-column-definition';

export const buildMonsterListColumns =
  (): DataTableColumnDefinition<MonsterListDefinition>[] => [
    {
      key: 'name',
      header: 'Name',
      sortable: true,
      sort_key: 'name',
      value: (row) => row.name,
    },
    {
      key: 'game_map',
      header: 'Game Map',
      value: (row) => row.game_map?.name ?? '—',
    },
    {
      key: 'max_level',
      header: 'Max Level',
      sortable: true,
      sort_key: 'max_level',
      value: (row) => row.max_level,
    },
    {
      key: 'xp',
      header: 'XP',
      sortable: true,
      sort_key: 'xp',
      value: (row) => row.xp,
    },
    {
      key: 'gold',
      header: 'Gold',
      sortable: true,
      sort_key: 'gold',
      value: (row) => row.gold,
    },
    {
      key: 'flags',
      header: 'Flags',
      value: (row) =>
        [
          row.is_celestial_entity ? 'Celestial' : null,
          row.is_raid_monster ? 'Raid Monster' : null,
          row.is_raid_boss ? 'Raid Boss' : null,
        ]
          .filter(Boolean)
          .join(', ') || '—',
    },
  ];
