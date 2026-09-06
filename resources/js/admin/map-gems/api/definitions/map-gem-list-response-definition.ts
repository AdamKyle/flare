import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import MapGemListDefinition from './map-gem-list-definition';

export type MapGemListResponseDefinition = PaginatedApiResponseDefinition<
  MapGemListDefinition[]
>;
