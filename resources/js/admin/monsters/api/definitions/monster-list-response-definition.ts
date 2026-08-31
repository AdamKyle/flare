import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import MonsterListDefinition from './monster-list-definition';

export type MonsterListResponseDefinition = PaginatedApiResponseDefinition<
  MonsterListDefinition[]
>;
