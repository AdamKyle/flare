import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import RaceDefinition from './race-definition';

export type RaceListResponseDefinition = PaginatedApiResponseDefinition<
  RaceDefinition[]
>;
