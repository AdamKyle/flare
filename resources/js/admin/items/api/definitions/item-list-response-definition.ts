import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import ItemDefinition from './item-definition';

export type ItemListResponseDefinition = PaginatedApiResponseDefinition<
  ItemDefinition[]
>;
