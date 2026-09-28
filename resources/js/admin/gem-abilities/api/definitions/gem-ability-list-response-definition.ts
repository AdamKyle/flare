import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import GemAbilityListDefinition from './gem-ability-list-definition';

export type GemAbilityListResponseDefinition = PaginatedApiResponseDefinition<
  GemAbilityListDefinition[]
>;
