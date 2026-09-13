import GameMapDefinition from '../api/definitions/game-map-definition';
import {
  renderGameMapListCell,
  renderGameMapListPlaneCell,
  renderGameMapListSourceCell,
  renderGameMapListTypeCell,
} from '../utils/render-game-map-list-cell';

import DataTableColumnDefinition from 'ui/data-table/types/data-table-column-definition';

export const GAME_MAP_LIST_COLUMNS: DataTableColumnDefinition<GameMapDefinition>[] =
  [
    {
      key: 'name',
      header: 'Name',
      sortable: true,
      sort_key: 'name',
      value: renderGameMapListCell,
    },
    {
      key: 'plane',
      header: 'Plane',
      value: renderGameMapListPlaneCell,
    },
    {
      key: 'map_type',
      header: 'Type',
      value: renderGameMapListTypeCell,
    },
    {
      key: 'source',
      header: 'Source',
      value: renderGameMapListSourceCell,
    },
  ];
