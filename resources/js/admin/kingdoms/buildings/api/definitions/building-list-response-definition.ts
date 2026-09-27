import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import BuildingListDefinition from './building-list-definition';

export type BuildingListResponseDefinition = PaginatedApiResponseDefinition<
  BuildingListDefinition[]
>;
