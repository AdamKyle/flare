import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import NpcListDefinition from './npc-list-definition';

export type NpcListResponseDefinition = PaginatedApiResponseDefinition<
  NpcListDefinition[]
>;
