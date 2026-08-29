import { PaginatedApiResponseDefinition } from 'api-handler/definitions/paginated-api-response-definition';

import LocationListDefinition from './location-list-definition';

export type LocationListResponseDefinition = PaginatedApiResponseDefinition<
  LocationListDefinition[]
>;
