import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import UnitListDefinition from './unit-list-definition';

export type UnitListResponseDefinition = PaginatedApiResponseDefinition<
  UnitListDefinition[]
>;
