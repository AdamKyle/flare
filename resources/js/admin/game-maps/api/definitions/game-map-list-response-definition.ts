import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import GameMapDefinition from './game-map-definition';

export type GameMapListResponseDefinition = PaginatedApiResponseDefinition<
  GameMapDefinition[]
>;
